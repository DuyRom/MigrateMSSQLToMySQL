<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyHandler 
{
    public static function addForeignKey()
    {
        $mssqlTables = DB::connection('sqlsrv')->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
        $mssqlTableNames = array_map(function ($table) {
            return $table->TABLE_NAME;
        }, $mssqlTables);

        $mysqlTables = DB::connection('mysql')->select("SHOW TABLES");
        $mysqlTableNames = array_map(function ($table) {
            return array_values((array) $table)[0];
        }, $mysqlTables);

        $existingTables = array_intersect($mssqlTableNames, $mysqlTableNames);
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($existingTables as $tableName) {
            try {
                // Lấy thông tin khóa ngoại từ MSSQL
                $foreignKeys = DB::connection('sqlsrv')->select("
                    SELECT 
                        KCU1.CONSTRAINT_NAME AS FK_CONSTRAINT_NAME,
                        KCU1.TABLE_NAME AS FK_TABLE_NAME,
                        KCU1.COLUMN_NAME AS FK_COLUMN_NAME,
                        KCU2.TABLE_NAME AS REFERENCED_TABLE_NAME,
                        KCU2.COLUMN_NAME AS REFERENCED_COLUMN_NAME
                    FROM 
                        INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS RC
                        INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KCU1
                            ON KCU1.CONSTRAINT_CATALOG = RC.CONSTRAINT_CATALOG
                            AND KCU1.CONSTRAINT_SCHEMA = RC.CONSTRAINT_SCHEMA
                            AND KCU1.CONSTRAINT_NAME = RC.CONSTRAINT_NAME
                        INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KCU2
                            ON KCU2.CONSTRAINT_CATALOG = RC.UNIQUE_CONSTRAINT_CATALOG
                            AND KCU2.CONSTRAINT_SCHEMA = RC.UNIQUE_CONSTRAINT_SCHEMA
                            AND KCU2.CONSTRAINT_NAME = RC.UNIQUE_CONSTRAINT_NAME
                            AND KCU2.ORDINAL_POSITION = KCU1.ORDINAL_POSITION
                    WHERE 
                        KCU1.TABLE_NAME = ?", [$tableName]);

                foreach ($foreignKeys as $fk) {
                    $fkColumnType = DB::connection('mysql')->getSchemaBuilder()->getColumnType($tableName, $fk->FK_COLUMN_NAME);
                    $referencedColumnType = DB::connection('mysql')->getSchemaBuilder()->getColumnType($fk->REFERENCED_TABLE_NAME, $fk->REFERENCED_COLUMN_NAME);

                    if ($fkColumnType !== $referencedColumnType) {
                        throw new \Exception("Data type mismatch between {$fk->FK_COLUMN_NAME} and {$fk->REFERENCED_COLUMN_NAME}");
                    }
 
                    Schema::connection('mysql')->table($tableName, function ($table) use ($fk) {
                        $table->foreign($fk->FK_COLUMN_NAME, $fk->FK_CONSTRAINT_NAME)
                              ->references($fk->REFERENCED_COLUMN_NAME)
                              ->on($fk->REFERENCED_TABLE_NAME)
                              ->onDelete('cascade');
                    });
                    

                    dump("Foreign key {$fk->FK_CONSTRAINT_NAME} added to table {$tableName}.");
                }

            } catch (\Exception $e) {
                dump("Error adding constraints to table {$tableName}: {$e->getMessage()}");

                DB::connection('mysql')->table('migration_errors')->updateOrInsert(
                    ['table_name' => $tableName],
                    [
                        'error_message' => $e->getMessage(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        dump("Table constraints added successfully.");
    }
}
