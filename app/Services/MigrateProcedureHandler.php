<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MigrateProcedureHandler
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Migrates stored procedures from SQL Server to MySQL.
     *
     * This method retrieves the list of stored procedures from the SQL Server database
     * and checks if they already exist in the MySQL database. If a stored procedure
     * does not exist in MySQL, it converts the procedure definition using an AI service
     * and creates the procedure in MySQL. If an error occurs during the migration process,
     * it logs the error message and saves the procedure details in the migration_errors table.
     *
     * @return void
     */
    public function migrateProcedures()
    {
        $procedures = DB::connection('sqlsrv')->select("
            SELECT SPECIFIC_NAME, ROUTINE_DEFINITION 
            FROM INFORMATION_SCHEMA.ROUTINES 
            WHERE ROUTINE_TYPE = 'PROCEDURE'
        ");

        $existingProcedures = DB::connection('mysql')->select("
            SELECT ROUTINE_NAME 
            FROM INFORMATION_SCHEMA.ROUTINES 
            WHERE ROUTINE_TYPE = 'PROCEDURE' AND ROUTINE_SCHEMA = DATABASE()
        ");

        $existingProcedureNames = array_map(function ($procedure) {
            return $procedure->ROUTINE_NAME;
        }, $existingProcedures);

        foreach ($procedures as $procedure) {
            $procedureName = $procedure->SPECIFIC_NAME;

            if (in_array($procedureName, $existingProcedureNames)) {
                dump("Stored procedure {$procedureName} already exists in MySQL. Skipping conversion.");
                continue;
            }

            $procedureDefinition = $procedure->ROUTINE_DEFINITION;
            $convertedProcedure = $this->openAIService->convertProcedure($procedureDefinition);

            try {
                $pdo = DB::connection('mysql')->getPdo();
                $pdo->exec("DROP PROCEDURE IF EXISTS `{$procedureName}`;");
                $pdo->exec($convertedProcedure);

                DB::connection('mysql')->table('migration_errors')->where('table_name', $procedureName)->delete();
                dump("Stored procedure {$procedureName} created successfully in MySQL.");
            } catch (\Exception $e) {
                // Log lỗi và lưu vào bảng migration_errors
                dump("Error creating stored procedure {$procedureName}. Check log for details.");
                DB::connection('mysql')->table('migration_errors')->updateOrInsert(
                    ['table_name' => $procedureName],
                    [
                        'error_message' => $e->getMessage(),
                        'view_definition' => $convertedProcedure,
                        'updated_at' => \Carbon\Carbon::now(),
                    ]
                );
            }
        }
    }
}
