<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Mail\MigrationCompleted;
use Illuminate\Support\Facades\Mail;
use App\Notifications\MigrationErrorNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class AutoIncrementPk
{
    /**
     * Migrate tables and set auto-increment for ID columns.
     */
    public static function migrateTables($toLower = false)
    {
        $tables = DB::connection('sqlsrv')->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
        $successfulMigrations = [];
        $failedMigrations = [];

        // Disable foreign key checks
        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            $tableName = $toLower ? strtolower($table->TABLE_NAME) : $table->TABLE_NAME;

            try {
                // Kiểm tra bảng tồn tại trong MySQL
                if (!Schema::connection('mysql')->hasTable($tableName)) {
                    Log::warning("Table {$tableName} does not exist in MySQL. Skipping auto-increment migration.");
                    continue;
                }

                $totalMigrated = 0;

                // Kiểm tra cột ID có auto-increment trong MSSQL
                $isAutoIncrement = DB::connection('sqlsrv')->selectOne(
                    "SELECT COLUMNPROPERTY(object_id(TABLE_NAME), COLUMN_NAME, 'IsIdentity') as IsIdentity
                     FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_NAME = ? AND COLUMN_NAME = 'id'",
                    [$table->TABLE_NAME]
                );

                if ($isAutoIncrement && $isAutoIncrement->IsIdentity && Schema::connection('mysql')->hasColumn($tableName, 'id')) {
                    $maxId = DB::connection('mysql')->table($tableName)->max('id') ?? 0;
                    $maxId = intval($maxId);
                    Log::info("Max ID of table {$tableName} is {$maxId}");

                    // Thiết lập auto-increment trong MySQL
                    DB::connection('mysql')->statement("ALTER TABLE {$tableName} MODIFY COLUMN id BIGINT AUTO_INCREMENT");
                    DB::connection('mysql')->statement("ALTER TABLE {$tableName} AUTO_INCREMENT = " . ($maxId + 1));

                    $totalMigrated++;
                }

                self::updateMigrationStatus($tableName, $totalMigrated, true);
                Log::info("Auto-increment for table {$tableName} migrated successfully.");
                $successfulMigrations[] = $tableName;

            } catch (\Exception $e) {
                self::handleMigrationError($tableName, $e, 'auto-increment', $totalMigrated ?? 0);
                $failedMigrations[] = $tableName;
            }
        }

        // Re-enable foreign key checks
        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=1');

        // Gửi email thông báo hoàn tất
        Mail::to(config('mail.to.address'))->send(new MigrationCompleted($successfulMigrations, $failedMigrations));
    }

    /**
     * Update migration status in the database.
     */
    private static function updateMigrationStatus($tableName, $recordsMigrated, $success)
    {
        DB::connection('mysql')->table('migration_status')->updateOrInsert(
            ['table_name' => $tableName],
            [
                'records_migrated' => $recordsMigrated,
                'migration_success' => $success,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Handle migration errors with detailed logging and notification.
     */
    private static function handleMigrationError($tableName, \Exception $e, $migrationType, $recordsMigrated = 0)
    {
        $errorMessage = $e->getMessage();
        $stackTrace = $e->getTraceAsString();

        Log::error("Error migrating {$migrationType} for table {$tableName}: {$errorMessage}\nStack Trace: {$stackTrace}");

        DB::connection('mysql')->table('migration_errors')->insert([
            'table_name' => $tableName,
            'error_message' => $errorMessage,
            'stack_trace' => $stackTrace,
            'migration_type' => $migrationType,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::updateMigrationStatus($tableName, $recordsMigrated, false);

        Notification::route('mail', config('mail.to.address'))
            ->notify(new MigrationErrorNotification($tableName, $errorMessage, $migrationType));
    }
}