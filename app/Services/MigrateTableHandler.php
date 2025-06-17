<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Mail\MigrationCompleted;
use Illuminate\Support\Facades\Mail;
use App\Notifications\MigrationErrorNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class MigrateTableHandler
{
    /**
     * Migrate schema and data from MSSQL to MySQL.
     */
    public static function migrateTables($toLower = false)
    {
        $tables = DB::connection('sqlsrv')->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
        $successfulMigrations = [];
        $failedMigrations = [];

        foreach ($tables as $table) {
            $tableName = $toLower ? strtolower($table->TABLE_NAME) : $table->TABLE_NAME;

            try {
                // Migrate schema
                self::migrateSchema($table->TABLE_NAME, $tableName, $toLower);

                // Kiểm tra dữ liệu đã được migrate
                $mysqlDataCount = DB::connection('mysql')->table($tableName)->count();
                if ($mysqlDataCount > 0) {
                    Log::info("Table {$tableName} already has {$mysqlDataCount} records in MySQL. Skipping data migration.");
                    $successfulMigrations[] = $tableName;
                    continue;
                }

                // Migrate dữ liệu
                $totalMigrated = self::migrateData($table->TABLE_NAME, $tableName);

                // Thiết lập auto-increment và primary key
                self::setAutoIncrementAndPrimaryKey($tableName, $totalMigrated);

                self::updateMigrationStatus($tableName, $totalMigrated, true);
                Log::info("Table {$tableName} migrated successfully with {$totalMigrated} records.");
                $successfulMigrations[] = $tableName;

            } catch (\Exception $e) {
                self::handleMigrationError($tableName, $e, 'schema-and-data', $totalMigrated ?? 0);
                $failedMigrations[] = $tableName;
            }
        }

        // Gửi email thông báo hoàn tất
        Mail::to(config('mail.to.address'))->send(new MigrationCompleted($successfulMigrations, $failedMigrations));
    }

    /**
     * Migrate schema for a table.
     */
    private static function migrateSchema($sourceTableName, $tableName, $toLower)
    {
        if (Schema::connection('mysql')->hasTable($tableName)) {
            Log::info("Table {$tableName} already exists in MySQL.");
            return;
        }

        $columns = DB::connection('sqlsrv')->select(
            "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE
             FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?",
            [$sourceTableName]
        );

        Schema::connection('mysql')->create($tableName, function ($table) use ($columns) {
            foreach ($columns as $column) {
                $columnName = $column->COLUMN_NAME;
                $dataType = strtolower($column->DATA_TYPE);
                $isNullable = $column->IS_NULLABLE === 'YES';
                $maxLength = $column->CHARACTER_MAXIMUM_LENGTH;
                $numericPrecision = $column->NUMERIC_PRECISION;
                $numericScale = $column->NUMERIC_SCALE;

                switch ($dataType) {
                    case 'int':
                    case 'bigint':
                        if (strtolower($columnName) === 'id' && config('database.id_auto_increment')) {
                            $table->bigIncrements($columnName);
                        } else {
                            $table->bigInteger($columnName)->nullable($isNullable);
                        }
                        break;
                    case 'varchar':
                    case 'nvarchar':
                        if ($maxLength == -1) {
                            $table->longText($columnName)->nullable($isNullable);
                        } elseif ($maxLength > 255) {
                            $table->text($columnName)->nullable($isNullable);
                        } else {
                            $table->string($columnName, $maxLength ?: 255)->nullable($isNullable);
                        }
                        break;
                    case 'text':
                    case 'ntext':
                        $table->longText($columnName)->nullable($isNullable);
                        break;
                    case 'decimal':
                    case 'numeric':
                        $table->decimal($columnName, $numericPrecision ?: 18, $numericScale ?: 4)->nullable($isNullable);
                        break;
                    case 'float':
                    case 'double':
                        $table->decimal($columnName, 18, 2)->nullable($isNullable);
                        break;
                    case 'date':
                        $table->date($columnName)->nullable($isNullable);
                        break;
                    case 'datetime':
                    case 'datetime2':
                        $table->dateTime($columnName)->nullable($isNullable);
                        break;
                    case 'bit':
                        $table->boolean($columnName)->nullable($isNullable);
                        break;
                    case 'image':
                    case 'varbinary':
                        $table->binary($columnName)->nullable(true);
                        break;
                    default:
                        Log::warning("Unsupported data type {$dataType} for column {$columnName} in table {$tableName}. Defaulting to string.");
                        $table->string($columnName)->nullable($isNullable);
                        break;
                }
            }
        });

        Log::info("Table {$tableName} schema created successfully in MySQL.");
    }

    /**
     * Migrate data for a table.
     */
    private static function migrateData($sourceTableName, $tableName)
    {
        $totalMigrated = 0;

        // Kiểm tra schema cột giữa MSSQL và MySQL
        $mssqlColumns = Schema::connection('sqlsrv')->getColumnListing($sourceTableName);
        $mysqlColumns = Schema::connection('mysql')->getColumnListing($tableName);

        if (array_diff($mssqlColumns, $mysqlColumns) || array_diff($mysqlColumns, $mssqlColumns)) {
            throw new \Exception("Schema mismatch between MSSQL and MySQL for table {$tableName}.");
        }

        if (Schema::connection('sqlsrv')->hasColumn($sourceTableName, 'id')) {
            $query = DB::connection('sqlsrv')->table($sourceTableName)->orderBy('id');
        } else {
            $firstColumn = $mssqlColumns[0] ?? null;
            if (!$firstColumn) {
                throw new \Exception("No columns found for table {$sourceTableName}.");
            }
            $query = DB::connection('sqlsrv')->table($sourceTableName)->orderBy($firstColumn);
        }

        // Sử dụng cursor để giảm tải bộ nhớ
        foreach ($query->cursor() as $row) {
            DB::connection('mysql')->table($tableName)->insert((array) $row);
            $totalMigrated++;
        }

        return $totalMigrated;
    }

    /**
     * Set auto-increment and primary key for a table.
     */
    private static function setAutoIncrementAndPrimaryKey($tableName, $totalMigrated)
    {
        if (Schema::connection('mysql')->hasColumn($tableName, 'id') && config('database.id_auto_increment')) {
            $maxId = DB::connection('mysql')->table($tableName)->max('id') ?? 0;
            $maxId = intval($maxId);
            DB::connection('mysql')->statement("ALTER TABLE {$tableName} AUTO_INCREMENT = " . ($maxId + 1));

            $primaryKey = DB::connection('mysql')->select("SHOW KEYS FROM {$tableName} WHERE Key_name = 'PRIMARY' AND Column_name = 'id'");
            if (empty($primaryKey) && config('database.primary_key') === 'id') {
                DB::connection('mysql')->statement("ALTER TABLE {$tableName} ADD PRIMARY KEY (id)");
            }

            Log::info("Auto-increment and primary key set for table {$tableName} with max ID {$maxId}.");
        }
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

        Log::error("Error migrating {$migrationType} for tableName} fortable {$ tableName}: {$errorMessage}\nStack Trace: {$stackTrace}");

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