<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Schema;
use App\Mail\MigrationCompleted;
use Illuminate\Support\Facades\Mail;
use App\Notifications\MigrationErrorNotification;
use Illuminate\Support\Facades\Notification;
use App\Services\SpecialViewHandler;

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
        //$this->getViewDefinition();
        //$this->migrateTables();
        $this->migrateViews();
        //$this->migrateStoredProcedures();

        $this->info('Database migration completed successfully.');
    }

    private function getViewDefinition()
    {
        $views = DB::connection('sqlsrv')->select('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS');
    
        foreach ($views as $view) {
            $viewName = $view->TABLE_NAME;
            
            if ($viewName === 'A_ThuChi_All') {
                $viewDefinition = DB::connection('sqlsrv')->select(DB::raw("EXEC sp_helptext '{$viewName}'"));
                $viewDefinitionText = '';
                foreach ($viewDefinition as $line) {
                    $viewDefinitionText .= $line->Text;
                }

                $this->info("View {$viewName} processed successfully.");
                $this->info("View definition length: " . strlen($viewDefinitionText));
                file_put_contents(storage_path('logs/view_definition_A_ThuChi_All.sql'), $viewDefinitionText);
                $this->info("View definition saved to file.");
            }
        }
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
        $views = DB::connection('sqlsrv')->select('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS');
        $failedViews = [];

        foreach ($views as $view) {
            $viewName = $view->TABLE_NAME;
            $viewDefinition = DB::connection('sqlsrv')->select(DB::raw("EXEC sp_helptext '{$viewName}'"));
            $viewDefinitionText = '';
            foreach ($viewDefinition as $line) {
                $viewDefinitionText .= $line->Text;
            }

            $viewDefinitionText = preg_replace('/\[(.*?)\]\./', '', $viewDefinitionText); // Remove schema references like [dbo].
            $viewDefinitionText = preg_replace('/\[(.*?)\]/', '`$1`', $viewDefinitionText); // Convert [column] to `column`

            if (preg_match('/TOP\s+\(100\)\s+PERCENT/i', $viewDefinitionText)) {
                $viewDefinitionText = preg_replace('/TOP\s+\(100\)\s+PERCENT/i', '', $viewDefinitionText); // Remove TOP (100) PERCENT
            } else {
                $viewDefinitionText = preg_replace_callback('/TOP\s+\((\d+)\)/i', function ($matches) {
                    return 'LIMIT ' . $matches[1];
                }, $viewDefinitionText);
            }

            // Replace CONVERT(varchar(10), ...) with DATE_FORMAT for MySQL compatibility
            $viewDefinitionText = preg_replace('/CONVERT\s*\(\s*varchar\s*\(10\)\s*,\s*(.*?),\s*23\s*\)/i', 'DATE_FORMAT($1, \'%Y-%m-%d\')', $viewDefinitionText);

            // Remove any leading "CREATE VIEW" or similar statements in the view definition
            $viewDefinitionText = preg_replace('/^CREATE\s+VIEW\s+`.*?`\s+AS\s+/i', '', $viewDefinitionText);

            // Ensure the view definition doesn't contain another CREATE VIEW statement
            $viewDefinitionText = preg_replace('/CREATE\s+VIEW\s+`.*?`\s+AS\s+/i', '', $viewDefinitionText);

            // Remove any remaining schema references like dbo.
            $viewDefinitionText = str_replace('dbo.', '', $viewDefinitionText);

            // Remove comments
            $viewDefinitionText = preg_replace('/\/\*.*?\*\//s', '', $viewDefinitionText);

            // Ensure no invalid variable replacements
            $viewDefinitionText = str_replace('$1', '0', $viewDefinitionText);
            $viewDefinitionText = str_replace('$2', '0', $viewDefinitionText);

            // Replace ISNULL with IFNULL for MySQL compatibility
            $viewDefinitionText = preg_replace('/ISNULL\s*\(([^,]+),\s*([^\)]+)\)/i', 'IFNULL($1, $2)', $viewDefinitionText);

            // Handle special views
            $viewDefinitionText = SpecialViewHandler::handleSpecialView($viewName, $viewDefinitionText);

            try {
                DB::connection('mysql')->statement("CREATE VIEW `{$viewName}` AS {$viewDefinitionText}");
                $this->info("View {$viewName} created successfully in MySQL.");
            } catch (\Exception $e) {
                \Log::error("Error creating view {$viewName}: " . $e->getMessage());
                $this->error("Error creating view {$viewName}. Check log for details.");

                $failedViews[] = [
                    'viewName' => $viewName,
                    'viewDefinition' => $viewDefinitionText,
                    'error' => $e->getMessage()
                ];
            }
            if ($viewName === 'A_ThuChi_All') {
                $this->info("View definition length: " . strlen($viewDefinitionText));
                file_put_contents(storage_path('logs/view_definition_A_ThuChi_All.sql'), $viewDefinitionText);
                $this->info("View definition saved to file.");
            }
        }

        // Retry failed views
        $this->retryFailViews($failedViews);
    }

    private function retryFailViews($failedViews, $maxRetries = 3)
    {
        $retryCount = 0;

        while (!empty($failedViews) && $retryCount < $maxRetries) {
            $retryCount++;
            $this->info("Retrying failed views, attempt {$retryCount} of {$maxRetries}...");
            $remainingFailedViews = [];

            foreach ($failedViews as $failedView) {
                try {
                    DB::connection('mysql')->statement("CREATE VIEW `{$failedView['viewName']}` AS {$failedView['viewDefinition']}");
                    $this->info("View {$failedView['viewName']} created successfully in MySQL on retry.");
                } catch (\Exception $e) {
                    \Log::error("Error creating view {$failedView['viewName']} on retry: " . $e->getMessage());
                    $this->error("Error creating view {$failedView['viewName']} on retry. Check log for details.");

                    $remainingFailedViews[] = $failedView;

                    DB::connection('mysql')->table('migration_errors')->insert([
                        'table_name' => $failedView['viewName'],
                        'error_message' => $e->getMessage(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $failedViews = $remainingFailedViews;
        }

        if (!empty($failedViews)) {
            $this->error("Some views could not be created after {$maxRetries} attempts. Check the migration_errors table for details.");
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
