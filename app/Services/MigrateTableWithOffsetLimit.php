<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Notifications\MigrationErrorNotification;
use Illuminate\Support\Facades\Notification;
use App\Jobs\MigrateTableJobWithOffsetLimit;
use Illuminate\Support\Facades\Log;

class MigrateTableWithOffsetLimit
{
    /**
     * Migrate schema from MSSQL to MySQL.
     */
    public static function migrateSchema($toLower = false)
    {
        $tables = DB::connection('sqlsrv')->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");

        foreach ($tables as $table) {
            $tableName = $toLower ? strtolower($table->TABLE_NAME) : $table->TABLE_NAME;

            try {
                // Kiểm tra schema đã tồn tại
                if (Schema::connection('mysql')->hasTable($tableName)) {
                    Log::info("Table {$tableName} already exists in MySQL. Skipping schema migration.");
                    continue;
                }

                $columns = DB::connection('sqlsrv')->select(
                    "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE
                     FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_NAME = ?", 
                    [$table->TABLE_NAME]
                );

                Schema::connection('mysql')->create($tableName, function ($table) use ($columns) "{
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
                                $table->bigInteger($columnName)->nullable($isNullable);
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
                                $table->double($columnName)->nullable($isNullable);
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
                                $table->binary($columnName)->nullable($isNullable);
                                break;
                            default:
                                Log::warning("Unsupported data type {$dataType} for column {$columnName} in table {$tableName}. Defaulting to string.");
                                $table->string($columnName)->nullable($isNullable);
                                break;
                        }
                    }
                });

                Log::info("Table {$tableName} schema created successfully in MySQL.");
                self::updateMigrationStatus($tableName, true);

            } catch (\Exception $e) {
                self::handleMigrationError($tableName, $e, 'schema');
            }
        }
    }

    /**
     * Migrate data from MSSQL to MySQL using queue.
     */
    public static function migrateData($toLower = false)
    {
        $tables = DB::connection('sqlsrv')->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");

        foreach ($tables as $table) {
            $tableName = $toLower ? strtolower($table->TABLE_NAME) : $table->TABLE_NAME;

            try {
                // Kiểm tra bảng tồn tại trong MySQL
                if (!Schema::connection('mysql')->hasTable($tableName)) {
                    Log::warning("Table {$tableName} does not exist in MySQL. Skipping data migration.");
                    continue;
                }

                // Kiểm tra dữ liệu đã được migrate
                $mysqlDataCount = DB::connection('mysql')->table($tableName)->count();
                if ($mysqlDataCount > 0) {
                    Log::info("Table {$tableName} already has {$mysqlDataCount} records in MySQL. Skipping data migration.");
                    continue;
                }

                // Kiểm tra số lượng bản ghi trong MSSQL
                $mssqlDataCount = DB::connection('sqlsrv')->table($table->TABLE_NAME)->count();
                if ($mssqlDataCount == 0) {
                    Log::info("Table {$tableName} has no data in MSSQL. Skipping data migration.");
                    continue;
                }

                $chunkSize = config('const.migrate.chunk_size', 1000);
                $idColumnExists = Schema::connection('sqlsrv')->hasColumn($table->TABLE_NAME, 'id');
                $idColumnType = null;

                if ($idColumnExists) {
                    $idColumnType = DB::connection('sqlsrv')
                        ->select("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = 'id'", [$table->TABLE_NAME]);

                    $idColumnType = !empty($idColumnType) ? $idColumnType[0]->DATA_TYPE : null;
                }

                Log::info("Starting data migration for table {$tableName} with {$mssqlDataCount} records.");

                if (!$idColumnExists || !in_array($idColumnType, ['int', 'bigint'])) {
                    MigrateTableJobWithOffsetLimit::dispatch($tableName, null, null, $chunkSize);
                } else {
                    $minId = DB::connection('sqlsrv')->table($table->TABLE_NAME)->min('id');
                    $maxId = DB::connection('sqlsrv')->table($table->TABLE_NAME)->max('id');

                    for ($startId = $minId; $startId <= $maxId; $startId += $chunkSize) {
                        $endId = $startId + $chunkSize - 1;
                        MigrateTableJobWithOffsetLimit::dispatch($tableName, $startId, $endId, $chunkSize);
                    }
                }

                self::updateMigrationStatus($tableName, true);

            } catch (\Exception $e) {
                self::handleMigrationError($tableName, $e, 'data');
            }
        }
    }

    /**
     * Update migration status in the database.
     */
    private static function updateMigrationStatus($tableName, $success)
    {
        DB::connection('mysql')->table('migration_status')->updateOrInsert(
            ['table_name' => $tableName],
            [
                'migration_success' => $success,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Handle migration errors with detailed logging and notification.
     */
    private static function handleMigrationError($tableName, \Exception $e, $type)
    {
        $errorMessage = $e->getMessage();
        $stackTrace = $e->getTraceAsString();

        Log::error("Error migrating {$type} for table {$tableName}: {$errorMessage}\nStack Trace: {$stackTrace}");

        DB::connection('mysql')->table('migration_errors')->updateOrInsert(
            ['table_name' => $tableName],
            [
                'error_message' => $errorMessage,
                'stack_trace' => $stackTrace,
                'migration_type' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        self::updateMigrationStatus($tableName, false);

        Notification::route('mail', config('mail.to.address'))
            ->notify(new MigrationErrorNotification($tableName, $errorMessage, $type));
    }
}