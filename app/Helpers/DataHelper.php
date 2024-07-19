<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class DataHelper
{
    /**
     * Update or insert migration status for a given table.
     *
     * @param string $tableName The name of the table.
     * @param int $totalMigrated The total number of records migrated.
     * @return void
     */
    public static function migrateStatus($tableName, $totalMigrated)
    {
        DB::connection('mysql')->table('migration_status')->updateOrInsert(
            ['table_name' => $tableName],
            [
                'records_migrated' => $totalMigrated,
                'migration_success' => true,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Migrates errors to the migration_errors table in the MySQL database.
     *
     * @param array $failedView The failed view details.
     * @param Exception $e The exception object containing the error message.
     * @return void
     */
    public static function migrateErrors($failedView, $e)
    {
        DB::connection('mysql')->table('migration_errors')->updateOrInsert(
            ['table_name' => $failedView['viewName']],
            [
                'error_message' => $e->getMessage(),
                'view_definition' => $failedView['viewDefinition'],  
                'updated_at' => now(),
            ]
        );
    }
}
