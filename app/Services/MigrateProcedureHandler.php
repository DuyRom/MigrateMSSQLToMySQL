<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MigrationProcedureHandler
{
    private function migrateStoredProcedures()
    {
        $procedures = DB::connection('sqlsrv')->select("SELECT SPECIFIC_NAME, ROUTINE_DEFINITION FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_TYPE = 'PROCEDURE'");

        foreach ($procedures as $procedure) {
            $procedureName = $procedure->SPECIFIC_NAME;
            $procedureDefinition = $procedure->ROUTINE_DEFINITION;
        
            try {
                DB::connection('mysql')->unprepared("DROP PROCEDURE IF EXISTS {$procedureName};");
                DB::connection('mysql')->unprepared("CREATE PROCEDURE {$procedureName} {$procedureDefinition}");
                dump("Stored Procedure {$procedureName} created successfully in MySQL.");
            } catch (\Exception $e) {
                \Log::error("Error creating stored procedure {$procedureName}: " . $e->getMessage());
                dump("Error creating stored procedure {$procedureName}. Check log for details.");

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
