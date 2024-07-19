<?php

namespace App\Services;

use App\Helpers\DataHelper;
use Illuminate\Support\Facades\DB;
use App\Services\SpecialViewHandler;
use App\Helpers\ViewHelper;

class MigrateViewHandler
{
    /**
     * Migrates views from SQL Server to MySQL.
     *
     * This method retrieves the list of views from the SQL Server database using the INFORMATION_SCHEMA.VIEWS table.
     * It then iterates over each view, retrieves its definition using the sp_helptext stored procedure,
     * and performs necessary modifications to the view definition text.
     * If the view does not exist in the MySQL database, it creates the view using the modified view definition text.
     * If an error occurs during the view creation, it logs the error and saves the view definition text to a file.
     * If the view already exists in the MySQL database, it skips the creation process.
     * Finally, it retries any failed views using the ViewHelper::retryFailViews() method.
     *
     * @return void
     */
    public static function migrateViews()
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

            $viewDefinitionText =  ViewHelper::viewDefinitionTextHandle($viewDefinitionText);

             $pattern = '/CREATE\s+VIEW\s+\w+\s+AS\s+/i';
             $viewDefinitionText = preg_replace($pattern, '', $viewDefinitionText);  

            $viewExists = DB::connection('mysql')->select("SHOW FULL TABLES WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . config('database.connections.mysql.database') . " = '{$viewName}'");

            if (empty($viewExists)) {
                try {
                    DB::connection('mysql')->statement("CREATE VIEW `{$viewName}` AS {$viewDefinitionText}");
                    DB::connection('mysql')->table('migration_errors')->where('table_name', $viewName)->delete();
                    dump("View {$viewName} created successfully in MySQL.");
                } catch (\Exception $e) {
                    if (SpecialViewHandler::create($viewName)) {
                        continue;
                    }
                    dump("Error creating view {$viewName}. Check log for details.");
                    $failedViews[] = [
                        'viewName' => $viewName,
                        'viewDefinition' => $viewDefinitionText,
                        'error' => $e->getMessage()
                    ];

                    DataHelper::migrateErrors(['viewName' => $viewName, 'viewDefinition' => $viewDefinitionText], $e);
                    file_put_contents(storage_path("logs/{$viewName}.sql"), $viewDefinitionText);
                }
            } else {
                dump("View {$viewName} already exists in MySQL. Skipping creation.");
            }
        }

        ViewHelper::retryFailViews();
    }

}
