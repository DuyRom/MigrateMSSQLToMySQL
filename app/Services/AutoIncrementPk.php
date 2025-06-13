<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Mail\MigrationCompleted;
use Illuminate\Support\Facades\Mail;
use App\Notifications\MigrationErrorNotification;
use Illuminate\Support\Facades\Notification;

class AutoIncrementPk
{
    public static function migrateTables($toLower = false)
    {
        $tables = DB::connection('sqlsrv')->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");

        $successfulMigrations = [];
        $failedMigrations = [];
        // set foreign key check to 0
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            $tableName = $table->TABLE_NAME;

            if ($toLower) {
                $tableName = strtolower($tableName);
            }

            try {
                $totalMigrated = 0;

                // Check if the table has an auto-incrementing ID in SQL Server
                $isAutoIncrement = DB::connection('sqlsrv')->selectOne("
                    SELECT COLUMNPROPERTY(object_id(TABLE_NAME), COLUMN_NAME, 'IsIdentity') as IsIdentity
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = ? AND COLUMN_NAME = 'id'", [$table->TABLE_NAME]);

                if ($isAutoIncrement && $isAutoIncrement->IsIdentity) {
                    // Check if the column exists in MySQL and is not auto-increment
                    if (Schema::connection('mysql')->hasColumn($tableName, 'id')) {
                        $maxId = DB::connection('mysql')->table($tableName)->max('id');
                        $maxId = intval($maxId);
                        dump("Max ID of table {$tableName} is {$maxId}");

                        // Set the auto-increment value in MySQL
                        DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN ID BIGINT AUTO_INCREMENT");
                        DB::statement("ALTER TABLE {$tableName} AUTO_INCREMENT = " . ($maxId + 1));

                        $totalMigrated++;
                    }
                }

                DB::connection('mysql')->table('migration_status')->updateOrInsert(
                    ['table_name' => $tableName],
                    [
                        'records_migrated' => $totalMigrated,
                        'migration_success' => true,
                        'updated_at' => now(),
                    ]
                );

                dump("Data of table {$tableName} migrated successfully.");
                $successfulMigrations[] = $tableName;
            } catch (\Exception $e) {
                dump("Error migrating table {$tableName}. ");

                DB::connection('mysql')->table('migration_errors')->insert([
                    'table_name' => $tableName,
                    'error_message' => $e->getMessage(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::connection('mysql')->table('migration_status')->updateOrInsert(
                    ['table_name' => $tableName],
                    [
                        'records_migrated' => $totalMigrated ?? 0,
                        'migration_success' => false,
                        'updated_at' => now(),
                    ]
                );

                $failedMigrations[] = $tableName;

                Notification::route('mail', config('mail.to.address'))
                            ->notify(new MigrationErrorNotification($tableName, $e->getMessage()));
                            
                \Log::error("Error migrating table {$tableName}: " . $e->getMessage());
                continue;
            }
        }

        // set foreign key check to 1
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Mail::to(config('mail.to.address'))->send(new MigrationCompleted($successfulMigrations, $failedMigrations));
    }
}
