<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Schema;
use App\Mail\MigrationCompleted;
use Illuminate\Support\Facades\Mail;
use App\Notifications\MigrationErrorNotification;
use Illuminate\Support\Facades\Notification;

class MigrateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate entire database from MSSQL to MySQL';

    /**
     * Execute the console command.
     *
     * @return int
     */

     public function __construct()
     {
         parent::__construct();
     }

     public function handle()
    {
        $this->migrateTables();
        //$this->migrateViews();
        //$this->migrateStoredProcedures();

        $this->info('Database migration completed successfully.');
    }

    private function migrateTables()
    {
        $tables = DB::connection('sqlsrv')->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");

        $successfulMigrations = [];
        $failedMigrations = [];

        foreach ($tables as $table) {
            $tableName = $table->TABLE_NAME;

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
                                        $table->increments($columnName);
                                    } else {
                                        $table->integer($columnName)->nullable($isNullable);
                                    }
                                    break;
                                case 'varchar':
                                case 'nvarchar':
                                    if ($maxLength > 255) {
                                        $this->info($columnName . ' ' . $maxLength);
                                        $table->text($columnName)->nullable($isNullable);
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

                    $this->info("Table {$tableName} created successfully in MySQL.");
                } else {
                    $this->info("Table {$tableName} already exists in MySQL.");
                }

                $mysqlDataCount = DB::connection('mysql')->table($tableName)->count();
                if ($mysqlDataCount > 0) {
                    $this->info("Table {$tableName} already has data in MySQL. Skipping data migration.");
                    continue;
                }

                $totalMigrated = 0;
                if (Schema::connection('sqlsrv')->hasColumn($tableName, 'id')) {
                    DB::connection('sqlsrv')->table($tableName)->orderBy('id')->chunk(1000, function ($rows) use ($tableName, &$totalMigrated) {
                        foreach ($rows as $row) {
                            DB::connection('mysql')->table($tableName)->insert((array) $row);
                            $totalMigrated++;
                        }
                        $this->info("Chunk of data for table {$tableName} migrated successfully.");
                    });
                } else {
                    $firstColumn = Schema::connection('sqlsrv')->getColumnListing($tableName)[0];
    
                    DB::connection('sqlsrv')->table($tableName)->orderBy($firstColumn)->chunk(1000, function ($rows) use ($tableName, &$totalMigrated) {
                        foreach ($rows as $row) {
                            DB::connection('mysql')->table($tableName)->insert((array) $row);
                            $totalMigrated++;
                        }
                        $this->info("Chunk of data for table {$tableName} migrated successfully.");
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

                $this->info("Data of table {$tableName} migrated successfully.");
                $successfulMigrations[] = $tableName;
            } catch (\Exception $e) {
                $this->error("Error migrating table {$tableName}. ");

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

    private function migrateViews()
    {
        $views = DB::connection('sqlsrv')->select('SELECT TABLE_NAME, VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS');

        foreach ($views as $view) {
            $viewName = $view->TABLE_NAME;
            $viewDefinition = $view->VIEW_DEFINITION;

            try {
                DB::connection('mysql')->statement("CREATE VIEW {$viewName} AS {$viewDefinition}");
                $this->info("View {$viewName} created successfully in MySQL.");
            } catch (\Exception $e) {
                \Log::error("Error creating view {$viewName}: " . $e->getMessage());
                $this->error("Error creating view {$viewName}. Check log for details.");

                DB::connection('mysql')->table('migration_errors')->insert([
                    'table_name' => $viewName,
                    'error_message' => $e->getMessage(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function migrateStoredProcedures()
    {
        $procedures = DB::connection('sqlsrv')->select("SELECT SPECIFIC_NAME, ROUTINE_DEFINITION FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_TYPE = 'PROCEDURE'");

        foreach ($procedures as $procedure) {
            $procedureName = $procedure->SPECIFIC_NAME;
            $procedureDefinition = $procedure->ROUTINE_DEFINITION;

            try {
                DB::connection('mysql')->unprepared("DROP PROCEDURE IF EXISTS {$procedureName};");
                DB::connection('mysql')->unprepared("CREATE PROCEDURE {$procedureName} {$procedureDefinition}");
                $this->info("Stored Procedure {$procedureName} created successfully in MySQL.");
            } catch (\Exception $e) {
                \Log::error("Error creating stored procedure {$procedureName}: " . $e->getMessage());
                $this->error("Error creating stored procedure {$procedureName}. Check log for details.");

                DB::connection('mysql')->table('migration_errors')->insert([
                    'table_name' => $procedureName,
                    'error_message' => $e->getMessage(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }


}
