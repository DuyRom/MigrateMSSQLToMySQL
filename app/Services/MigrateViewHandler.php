<?php

namespace App\Services;

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

            // if ($viewName === 'B_DV_Phieu_LoaiPhieu') {
            //     SpecialViewHandler::BDVPhieuLoaiPhieu();
            //     continue;
            // }

            $viewDefinition = DB::connection('sqlsrv')->select(DB::raw("EXEC sp_helptext '{$viewName}'"));
            $viewDefinitionText = '';
            foreach ($viewDefinition as $line) {
                $viewDefinitionText .= $line->Text;
            }

            $viewDefinitionText =  ViewHelper::viewDefinitionTextHandle($viewDefinitionText);

             // Handle special views
             $viewDefinitionText = SpecialViewHandler::handleSpecialView($viewName, $viewDefinitionText);

             // Ensure no CREATE VIEW statements remain
             $pattern = '/CREATE\s+VIEW\s+\w+\s+AS\s+/i';
             $viewDefinitionText = preg_replace($pattern, '', $viewDefinitionText);  

            // Check if the view already exists in MySQL
            $viewExists = DB::connection('mysql')->select("SHOW FULL TABLES WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . config('database.connections.mysql.database') . " = '{$viewName}'");

            if (empty($viewExists)) {
                try {
                    DB::connection('mysql')->statement("CREATE VIEW `{$viewName}` AS {$viewDefinitionText}");
                    DB::connection('mysql')->table('migration_errors')->where('table_name', $viewName)->delete();
                    dump("View {$viewName} created successfully in MySQL.");
                } catch (\Exception $e) {
                    \Log::error("Error creating view {$viewName}: " . $e->getMessage());
                    dump("Error creating view {$viewName}. Check log for details.");
                    $failedViews[] = [
                        'viewName' => $viewName,
                        'viewDefinition' => $viewDefinitionText,
                        'error' => $e->getMessage()
                    ];

                    file_put_contents(storage_path("logs/{$viewName}.sql"), $viewDefinitionText);
                }
            } else {
                dump("View {$viewName} already exists in MySQL. Skipping creation.");
            }
        }

        //ViewHelper::createCustomView();
        ViewHelper::retryFailViews($failedViews);
    }

}
