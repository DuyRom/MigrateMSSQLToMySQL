<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class DataHelper
{
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
