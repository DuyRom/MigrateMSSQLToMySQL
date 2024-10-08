<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Schema;
use App\Mail\MigrationCompleted;
use Illuminate\Support\Facades\Mail;
use App\Notifications\MigrationErrorNotification;
use Illuminate\Support\Facades\Notification;


class MigrateTableHandler
{
    public static function migrateTables($toLower = false)
    {
        $tables = DB::connection('sqlsrv')->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");

        $successfulMigrations = [];
        $failedMigrations = [];

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
                                    if (strtolower($columnName) === 'id' && config('database.id_auto_increment')) {
                                        $table->bigIncrements($columnName);
                                    } else {
                                        $table->bigInteger($columnName)->nullable($isNullable);
                                    }
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

                $totalMigrated = 0;
                if (Schema::connection('sqlsrv')->hasColumn($tableName, 'id')) {
                    DB::connection('sqlsrv')->table($tableName)->orderBy('id')->chunk(1000, function ($rows) use ($tableName, &$totalMigrated) {
                        foreach ($rows as $row) {
                            DB::connection('mysql')->table($tableName)->insert((array) $row);
                            $totalMigrated++;
                        }
                        dump("Chunk of data for table {$tableName} migrated successfully.");
                    });
                } else {
                    $firstColumn = Schema::connection('sqlsrv')->getColumnListing($tableName)[0];
    
                    DB::connection('sqlsrv')->table($tableName)->orderBy($firstColumn)->chunk(1000, function ($rows) use ($tableName, &$totalMigrated) {
                        foreach ($rows as $row) {
                            DB::connection('mysql')->table($tableName)->insert((array) $row);
                            $totalMigrated++;
                        }
                        dump("Chunk of data for table {$tableName} migrated successfully.");
                    });
                }

                if (Schema::connection('mysql')->hasColumn($tableName, 'id') && config('database.id_auto_increment')) {
                    $maxId = DB::connection('mysql')->table($tableName)->max('id');
                    $maxId = intval($maxId);
                    DB::statement("ALTER TABLE {$tableName} AUTO_INCREMENT = " . ($maxId + 1));
                    
                    $primaryKey = DB::select(DB::raw("SHOW KEYS FROM {$tableName} WHERE Key_name = 'PRIMARY' AND Column_name = 'id'"));
                    if (empty($primaryKey) && config('database.primary_key') === 'id'){
                        DB::statement("ALTER TABLE {$tableName} ADD PRIMARY KEY (id)");
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

        Mail::to(config('mail.to.address'))->send(new MigrationCompleted($successfulMigrations, $failedMigrations));
    }

}
