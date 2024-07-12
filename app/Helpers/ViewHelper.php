<?php

namespace App\Helpers;
use Illuminate\Support\Facades\DB;

class ViewHelper
{
    public static function viewDefinitionTextHandle($viewDefinitionText)
    {
        // Remove schema references like [dbo].
        $viewDefinitionText = preg_replace('/\[(.*?)\]\./', '', $viewDefinitionText); 

        // Convert [column] to `column`
        $viewDefinitionText = preg_replace('/\[(.*?)\]/', '`\$1`', $viewDefinitionText); 

        // Remove TOP (100) PERCENT
        if (preg_match('/TOP\s+\(100\)\s+PERCENT/i', $viewDefinitionText)) {
            $viewDefinitionText = preg_replace('/TOP\s+\(100\)\s+PERCENT/i', '', $viewDefinitionText); 
        } else {
            $viewDefinitionText = preg_replace_callback('/TOP\s+\((\d+)\)/i', function ($matches) {
                return 'LIMIT ' . $matches[1];
            }, $viewDefinitionText);
        }

        // Replace CONVERT(varchar(10), ...) with DATE_FORMAT for MySQL compatibility
        $viewDefinitionText = preg_replace_callback('/CONVERT\s*\(\s*varchar\s*\(10\)\s*,\s*(.*?),\s*23\s*\)/i', function ($matches) {
            return 'DATE_FORMAT(' . $matches[1] . ', \'%Y-%m-%d\')';
        }, $viewDefinitionText);

         // Replace CONVERT(varchar(5), ..., 108) with DATE_FORMAT for MySQL compatibility
         $viewDefinitionText = preg_replace_callback('/CONVERT\s*\(\s*varchar\s*\(5\)\s*,\s*(.*?),\s*108\s*\)/i', function ($matches) {
            return 'DATE_FORMAT(' . $matches[1] . ', \'%H:%i\')';
        }, $viewDefinitionText);

        // Replace CONVERT(int, REPLACE(...)) with CAST(REPLACE(...) AS SIGNED) for MySQL compatibility
        $viewDefinitionText = preg_replace_callback('/CONVERT\s*\(\s*int\s*,\s*REPLACE\s*\((.*?)\)\s*\)/i', function ($matches) {
            return 'CAST(REPLACE(' . $matches[1] . ') AS SIGNED)';
        }, $viewDefinitionText);

        // Replace CONVERT(int, ...) with CAST(... AS SIGNED) for MySQL compatibility
        $viewDefinitionText = preg_replace_callback('/CONVERT\s*\(\s*int\s*,\s*(.*?)\s*\)/i', function ($matches) {
            return 'CAST(' . $matches[1] . ' AS SIGNED)';
        }, $viewDefinitionText);

        // Replace ISNULL with IFNULL for MySQL compatibility
        $viewDefinitionText = str_replace('ISNULL(', 'IFNULL(', $viewDefinitionText);

        // Replace GETDATE() with NOW() for MySQL compatibility
        $viewDefinitionText = str_replace('GETDATE()', 'NOW()', $viewDefinitionText);

        // Remove any leading "CREATE VIEW" or similar statements in the view definition
        $viewDefinitionText = preg_replace('/^CREATE\s+VIEW\s+`.*?`\s+AS\s+/i', '', $viewDefinitionText);

        // Ensure the view definition doesn't contain another CREATE VIEW statement
        $viewDefinitionText = preg_replace('/CREATE\s+VIEW\s+`.*?`\s+AS\s+/i', '', $viewDefinitionText);

        // Remove any remaining schema references like dbo.
        $viewDefinitionText = str_replace('dbo.', '', $viewDefinitionText);

        // Remove comments
        $viewDefinitionText = preg_replace('/\/\*.*?\*\//s', '', $viewDefinitionText);

        // Remove CREATE VIEW statement ... AS
        $pattern = '/CREATE\s+VIEW\s+\w+\s+AS\s+/i';
        $viewDefinitionText = preg_replace($pattern, '', $viewDefinitionText);

        // Check if the view uses UNPIVOT
        if (stripos($viewDefinitionText, 'UNPIVOT') !== false) {
            // Extract the part of the query that uses UNPIVOT
            preg_match('/UNPIVOT\s*\((.*?)\s*FOR\s*(.*?)\s*IN\s*\((.*?)\)\s*\)/is', $viewDefinitionText, $matches);
            if ($matches) {
                list($fullMatch, $valueColumn, $pivotColumn, $columns) = $matches;

                // Remove the UNPIVOT part from the original query
                $viewDefinitionText = str_replace($fullMatch, '', $viewDefinitionText);

                // Extract the table name from the original query
                preg_match('/FROM\s*`?(\w+)`?\s*/i', $viewDefinitionText, $tableMatches);
                $tableName = $tableMatches[1] ?? 'source_table';

                // Prepare the UNION ALL query to replace UNPIVOT
                $columnsArray = array_map('trim', explode(',', $columns));
                $unionAllQueries = [];

                foreach ($columnsArray as $column) {
                    $unionAllQueries[] = "SELECT *, '$column' AS `$pivotColumn`, `$column` AS `$valueColumn` FROM `$tableName` WHERE `$column` <> 0";
                }

                $unionAllQuery = implode(" UNION ALL ", $unionAllQueries);

                // Replace the original subquery with the UNION ALL query
                $viewDefinitionText = preg_replace('/\(\s*SELECT\s*\*\s*FROM\s*`?' . preg_quote($tableName, '/') . '`?\s*\)\s*p/i', "($unionAllQuery)", $viewDefinitionText);
            }
        }

        // Check if the view uses PIVOT
        if (stripos($viewDefinitionText, 'PIVOT') !== false) {
            // Extract the part of the query that uses PIVOT
            preg_match('/PIVOT\s*\((.*?)\s*FOR\s*(.*?)\s*IN\s*\((.*?)\)\s*\)/is', $viewDefinitionText, $matches);
            if ($matches) {
                list($fullMatch, $aggregateFunction, $pivotColumn, $columns) = $matches;

                // Remove the PIVOT part from the original query
                $viewDefinitionText = str_replace($fullMatch, '', $viewDefinitionText);

                // Extract the table name from the original query
                preg_match('/FROM\s*`?(\w+)`?\s*/i', $viewDefinitionText, $tableMatches);
                $tableName = $tableMatches[1] ?? 'source_table';

                // Prepare the UNION ALL query to replace PIVOT
                $columnsArray = array_map('trim', explode(',', $columns));
                $unionAllQueries = [];

                foreach ($columnsArray as $column) {
                    $unionAllQueries[] = "SELECT *, '$column' AS `$pivotColumn`, $aggregateFunction AS `$column` FROM `$tableName`";
                }

                $unionAllQuery = implode(" UNION ALL ", $unionAllQueries);

                // Replace the original subquery with the UNION ALL query
                $viewDefinitionText = preg_replace('/\(\s*SELECT\s*\*\s*FROM\s*`?' . preg_quote($tableName, '/') . '`?\s*\)\s*p/i', "($unionAllQuery)", $viewDefinitionText);
            }
        }

        // // Remove alias from subquery
        // $viewDefinitionText = preg_replace('/\)\s*AS\s*\w+/i', ')', $viewDefinitionText);

        // // Remove alias from subquery
        // $viewDefinitionText = preg_replace('/\)\s*\w+/i', ')', $viewDefinitionText);

        return $viewDefinitionText;
    }
    
    public static function createCustomView()
    {
        $viewFiles = glob(database_path('views/*.php'));
        $failedViews = [];

        foreach ($viewFiles as $viewFile) {
            require_once $viewFile;
            $className = pathinfo($viewFile, PATHINFO_FILENAME);
            if (class_exists($className)) {
                try {
                    $viewData = $className::create();
                    $viewName = $viewData['viewName'];
                    $viewDefinitionText = $viewData['viewDefinitionText'];

                    $exists = DB::connection('mysql')->select("SHOW FULL TABLES IN `" . env('DB_DATABASE') . "` WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = ?", [$viewName]);

                    if (empty($exists)) {
                        DB::connection('mysql')->statement($viewDefinitionText);
                        dump("View $viewName created successfully in MySQL.");
                    } else {
                        dump("View $viewName already exists in MySQL.");
                    }
                } catch (\Exception $e) {
                    dump($e->getMessage());
                    $failedViews[] = [
                        'viewName' => $viewName,
                        'viewDefinition' => $viewDefinitionText
                    ];
                    
                    continue;
                }
            }
        }

        if (!empty($failedViews)) {
            self::retryFailViews($failedViews);
        } else {
            dump('All custom views created successfully in MySQL.');
        }
    }

    public static function retryFailViews($failedViews)
    {
        $maxRetries = 3; 
        $retryCount = 0;

        while (!empty($failedViews) && $retryCount < $maxRetries) {
            $retryCount++;
            dump("Retrying failed views, attempt {$retryCount} of {$maxRetries}...");
            $remainingFailedViews = [];

            foreach ($failedViews as $failedView) {
                // Check if the view already exists in MySQL
                $viewExists = DB::connection('mysql')->select("SHOW FULL TABLES WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . config('database.connections.mysql.database') . " = '{$failedView['viewName']}'");

                if (empty($viewExists)) {
                    try {
                        DB::connection('mysql')->statement("CREATE VIEW `{$failedView['viewName']}` AS {$failedView['viewDefinition']}");
                        dump("View {$failedView['viewName']} created successfully in MySQL on retry.");
                    } catch (\Exception $e) {
                        \Log::error("Error creating view {$failedView['viewName']} on retry: " . $e->getMessage());
                        dump("Error creating view {$failedView['viewName']} on retry. Check log for details.");

                        // Add to remaining failed views for the next retry
                        $remainingFailedViews[] = $failedView;

                        // Log error to migration_errors table
                        DB::connection('mysql')->table('migration_errors')->updateOrInsert(
                            ['table_name' => $failedView['viewName']],
                            [
                                'error_message' => $e->getMessage(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                } else {
                    dump("View {$failedView['viewName']} already exists in MySQL. Skipping creation.");
                }
            }

            $failedViews = $remainingFailedViews;
        }

        if (!empty($failedViews)) {
            dump("Some views could not be created after {$maxRetries} attempts. Check the migration_errors table for details.");
        }
        
    }
}
