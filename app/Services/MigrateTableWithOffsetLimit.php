<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Schema;
use App\Notifications\MigrationErrorNotification;
use Illuminate\Support\Facades\Notification;
use App\Jobs\MigrateTableJobWithOffsetLimit;

class MigrateTableWithOffsetLimit
{
    public static function migrateTables($toLower = false)
    {
        $tables = DB::connection('sqlsrv')->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");

        foreach ($tables as $table) {
            $tableName = $table->TABLE_NAME;

            if ($toLower) {
                $tableName = strtolower($tableName);
            }

            try {
                $columns = DB::connection('sqlsrv')->select("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?", [$tableName]);

                if (!Schema::connection('mysql')->hasTable($tableName)) {
                    Schema::connection('mysql')->create($tableName, function ($table) use ($columns) {
                        foreach ($columns as $column) {
                            $columnName = $column->COLUMN_NAME;
                            $dataType = $column->DATA_TYPE;
                            $isNullable = $column->IS_NULLABLE === 'YES';
                            $maxLength = $column->CHARACTER_MAXIMUM_LENGTH;

                            switch ($dataType) {
                                case 'int':
                                    $table->bigInteger($columnName)->nullable($isNullable);
                                    break;
                                case 'varchar':
                                case 'nvarchar':
                                    if ($maxLength > 255) {
                                        $table->text($columnName)->nullable($isNullable);
                                    } elseif ($maxLength == -1) { 
                                        $table->longText($columnName)->nullable($isNullable);
                                    } else {
                                        $table->string($columnName, $maxLength)->nullable($isNullable);
                                    }
                                    break;
                                case 'text':
                                    $table->text($columnName)->nullable($isNullable);
                                    break;
                                case 'ntext':
                                    $table->longText($columnName)->nullable($isNullable);
                                    break;
                                case 'float':
                                    $table->decimal($columnName, 18, 4)->nullable($isNullable);
                                    break;
                                case 'decimal':
                                    $table->decimal($columnName, 18, 4)->nullable($isNullable);
                                    break;
                                case 'double':
                                    $table->decimal($columnName, 18, 4)->nullable($isNullable);
                                    break;
                                case 'date':
                                    $table->date($columnName)->nullable($isNullable);
                                    break;
                                case 'datetime':
                                    $table->dateTime($columnName)->nullable($isNullable);
                                    break;
                                case 'bit':
                                    $table->boolean($columnName)->nullable($isNullable);
                                    break;
                                case 'image':
                                    $table->binary($columnName)->nullable($isNullable);
                                    break;
                                default:
                                    $table->string($columnName)->nullable($isNullable);
                                    break;
                            }
                        }
                    });

                    dump("Table {$tableName} created successfully in MySQL.");
                } else {
                    dump("Table {$tableName} already exists in MySQL.");
                }

                $mysqlDataCount = DB::connection('mysql')->table($tableName)->count();
                if ($mysqlDataCount > 0) {
                    dump("Table {$tableName} already has data in MySQL. Skipping data migration.");
                    continue;
                }

                $chunkSize = config('const.migrate.chunk_size');

                $idColumnExists = Schema::connection('sqlsrv')->hasColumn($tableName, 'id');
                $idColumnType = null;

                if ($idColumnExists) {
                    $idColumnType = DB::connection('sqlsrv')
                        ->select("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = 'id'", [$tableName]);

                    if (!empty($idColumnType)) {
                        $idColumnType = $idColumnType[0]->DATA_TYPE;
                    }
                }

                if (!$idColumnExists || !in_array($idColumnType, ['int', 'bigint'])) {
                    MigrateTableJobWithOffsetLimit::dispatch($tableName, null, null, $chunkSize);
                } else {
                    $minId = DB::connection('sqlsrv')->table($tableName)->min('id'); 
                    $maxId = DB::connection('sqlsrv')->table($tableName)->max('id'); 

                    for ($startId = $minId; $startId <= $maxId; $startId += $chunkSize) {
                        $endId = $startId + $chunkSize - 1;
                        MigrateTableJobWithOffsetLimit::dispatch($tableName, $startId, $endId, $chunkSize);
                    }
                }

                DB::connection('mysql')->table('migration_status')->updateOrInsert(
                    ['table_name' => $tableName],
                    [
                        'migration_success' => true,
                        'updated_at' => now(),
                    ]
                );

            } catch (\Exception $e) {
                dump("Error migrating table {$tableName}. ");

                DB::connection('mysql')->table('migration_errors')->updateOrInsert(
                    ['table_name' => $tableName],
                    [
                        'error_message' => $e->getMessage(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                DB::connection('mysql')->table('migration_status')->updateOrInsert(
                    ['table_name' => $tableName],
                    [
                        'migration_success' => false,
                        'updated_at' => now(),
                    ]
                );

                Notification::route('mail', config('mail.to.address'))
                            ->notify(new MigrationErrorNotification($tableName, $e->getMessage()));
                            
                \Log::error("Error migrating table {$tableName}: " . $e->getMessage());
                continue;
            }
        }
    }
}
