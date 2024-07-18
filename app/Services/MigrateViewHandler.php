<?php

namespace App\Services;

use App\Helpers\DataHelper;
use Illuminate\Support\Facades\DB;
use App\Services\SpecialViewHandler;
use App\Helpers\ViewHelper;

class MigrateViewHandler
{
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
