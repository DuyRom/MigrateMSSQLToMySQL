<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Helpers\DataHelper;

class MigrateProcedureHandlerOld
{
    public static function procedureDefinitionTextHandle($procedureDefinitionText)
    {
        // Remove schema references like [dbo].
        $procedureDefinitionText = preg_replace('/\[(.*?)\]\./', '', $procedureDefinitionText);

        // Remove [ and ] around the stored procedure name
        $procedureDefinitionText = preg_replace('/\[(.*?)\]/', '$1', $procedureDefinitionText);

        // Replace NVARCHAR with VARCHAR
        $procedureDefinitionText = str_ireplace('NVARCHAR', 'VARCHAR', $procedureDefinitionText);

        // Replace CONVERT(varchar(10), ...) with DATE_FORMAT for MySQL compatibility
        $procedureDefinitionText = preg_replace_callback('/CONVERT\s*\(\s*varchar\s*\(10\)\s*,\s*(.*?),\s*23\s*\)/i', function ($matches) {
            return 'DATE_FORMAT(' . $matches[1] . ', \'%Y-%m-%d\')';
        }, $procedureDefinitionText);

        
        // Replace CONVERT(varchar(5), ..., 108) with DATE_FORMAT for MySQL compatibility
        $procedureDefinitionText = preg_replace_callback('/CONVERT\s*\(\s*varchar\s*\(5\)\s*,\s*(.*?),\s*108\s*\)/i', function ($matches) {
            return 'DATE_FORMAT(' . $matches[1] . ', \'%H:%i\')';
        }, $procedureDefinitionText);

        // Replace CONVERT(int, REPLACE(...)) with CAST(REPLACE(...) AS SIGNED) for MySQL compatibility
        $procedureDefinitionText = preg_replace_callback('/CONVERT\s*\(\s*int\s*,\s*REPLACE\s*\((.*?)\)\s*\)/i', function ($matches) {
            return 'CAST(REPLACE(' . $matches[1] . ') AS SIGNED)';
        }, $procedureDefinitionText);

        // Replace CONVERT(int, ...) with CAST(... AS SIGNED) for MySQL compatibility
        $procedureDefinitionText = preg_replace_callback('/CONVERT\s*\(\s*int\s*,\s*(.*?)\s*\)/i', function ($matches) {
            return 'CAST(' . $matches[1] . ' AS SIGNED)';
        }, $procedureDefinitionText);

        // Replace ISNULL with IFNULL for MySQL compatibility
        $procedureDefinitionText = str_replace('ISNULL(', 'IFNULL(', $procedureDefinitionText);

        // Replace GETDATE() with NOW() for MySQL compatibility
        $procedureDefinitionText = str_replace('GETDATE()', 'NOW()', $procedureDefinitionText);

        // Remove any remaining schema references like dbo.
        $procedureDefinitionText = str_replace('dbo.', '', $procedureDefinitionText);

        // Remove comments
        $procedureDefinitionText = preg_replace('/\/\*.*?\*\//s', '', $procedureDefinitionText);

        // Remove line comments
        $procedureDefinitionText = preg_replace('/--.*$/m', '', $procedureDefinitionText);

        // Remove SET NOCOUNT ON
       $procedureDefinitionText = preg_replace('/SET\s+NOCOUNT\s+ON\s*/i', '', $procedureDefinitionText);

        // Convert stored procedure parameters
        $procedureDefinitionText = preg_replace_callback('/@(\w+)\s+(\w+(\(\d+\))?)/', function ($matches) {
            return 'IN ' . $matches[1] . ' ' . $matches[2];
        }, $procedureDefinitionText);

        // Convert CREATE PROCEDURE syntax
        $procedureDefinitionText = preg_replace('/CREATE\s+PROCEDURE\s+(\w+)\s*/is', 'CREATE PROCEDURE $1 (', $procedureDefinitionText);
         // Debugging - print procedureDefinitionText before and after change
         //echo "Before change: \n" . $procedureDefinitionText . "\n\n";
        // Ensure the closing parenthesis in the CREATE PROCEDURE declaration
        $procedureDefinitionText = preg_replace('/\bBEGIN\b/', ') BEGIN', $procedureDefinitionText, 1);
        $procedureDefinitionText = preg_replace('/\bAS\b/', '', $procedureDefinitionText, 1);
        $procedureDefinitionText = preg_replace('/(?<!;)\s+END\b/', '; END', $procedureDefinitionText);

        //echo "After change: \n" . $procedureDefinitionText . "\n\n";
        // Convert [column] to `column`, but only within the body of the procedure
        $procedureDefinitionText = preg_replace_callback('/CREATE\s+PROCEDURE\s+(\w+)\s*\((.*?)\)\s*BEGIN\s*(.*)\s*END;/is', function ($matches) {
            $procedureBody = preg_replace('/\[(.*?)\]/', '`$1`', $matches[3]);
            return 'CREATE PROCEDURE ' . $matches[1] . ' (' . $matches[2] . ') BEGIN ' . $procedureBody . ' END;';
        }, $procedureDefinitionText);

        // Remove any extraneous BEGIN keywords that are not needed
        $procedureDefinitionText = preg_replace('/\bBEGIN\s+BEGIN\b/i', 'BEGIN', $procedureDefinitionText);

        // Remove any extraneous IN keywords in WHERE clauses
        $procedureDefinitionText = preg_replace('/\bIN\s+(\w+)\b/', '$1', $procedureDefinitionText);

        // Ensure the closing parenthesis in the CREATE PROCEDURE declaration
        $procedureDefinitionText = preg_replace('/CREATE PROCEDURE (.*?)\((.*?)\s+BEGIN/i', 'CREATE PROCEDURE $1($2) BEGIN', $procedureDefinitionText);

        return $procedureDefinitionText;
    }

    public static function create()
    {
        $procedures = DB::connection('sqlsrv')->select("SELECT SPECIFIC_NAME, ROUTINE_DEFINITION FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_TYPE = 'PROCEDURE'");

        foreach ($procedures as $procedure) {
            $procedureName = $procedure->SPECIFIC_NAME;
            $procedureDefinition = $procedure->ROUTINE_DEFINITION;

            // Chuyển đổi cú pháp và kiểu dữ liệu từ MSSQL sang MySQL
            $procedureDefinition = self::procedureDefinitionTextHandle($procedureDefinition);

            // Thêm delimiter cho MySQL
            // $procedureDefinition = "DELIMITER //\n" . $procedureDefinition . "\n//\nDELIMITER ;";

            try {
                // Tạo kết nối PDO trực tiếp
                $pdo = DB::connection('mysql')->getPdo();
            
                // Thực hiện câu lệnh DROP PROCEDURE
                $pdo->exec("DROP PROCEDURE IF EXISTS `{$procedureName}`;");

                // Thực hiện câu lệnh CREATE PROCEDURE
                $pdo->exec($procedureDefinition);

                DB::connection('mysql')->table('migration_errors')->where('table_name', $procedureName)->delete();
                dump("Stored procedure {$procedureName} created successfully in MySQL.");
            } catch (\Exception $e) {
                \Log::error("Error creating stored procedure {$procedureName}: " . $e->getMessage());
                dump("Error creating stored procedure {$procedureName}. Check log for details.");

                // insert or update error record
                DB::connection('mysql')->table('migration_errors')->updateOrInsert(
                    ['table_name' => $procedureName],
                    [
                        'error_message' => $e->getMessage(),
                        'view_definition' => $procedureDefinition,
                        'updated_at' => \Carbon\Carbon::now(),
                    ]
                );
            }
        }
    }

}
