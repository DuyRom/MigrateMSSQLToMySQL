<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTableConstraintHandler 
{
    public static function addTableConstraints()
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

        foreach ($existingTables as $tableName) {
            try {
                // Lấy thông tin khóa chính từ MSSQL
                // $primaryKeys = DB::connection('sqlsrv')->select("
                //     SELECT COLUMN_NAME 
                //     FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                //     WHERE OBJECTPROPERTY(OBJECT_ID(CONSTRAINT_SCHEMA + '.' + CONSTRAINT_NAME), 'IsPrimaryKey') = 1 
                //     AND TABLE_NAME = ?", [$tableName]);

                // if (!empty($primaryKeys)) {
                //     $primaryKeyColumns = array_map(function ($pk) {
                //         return $pk->COLUMN_NAME;
                //     }, $primaryKeys);

                //     $existingPrimaryKey = DB::connection('mysql')->select(DB::raw("SHOW KEYS FROM {$tableName} WHERE Key_name = 'PRIMARY'"));
                //     if (empty($existingPrimaryKey)) {
                //         Schema::connection('mysql')->table($tableName, function ($table) use ($primaryKeyColumns) {
                //             $table->primary($primaryKeyColumns);
                //         });
                //     }
                // }

                // Lấy thông tin khóa ngoại từ MSSQL
                // $foreignKeys = DB::connection('sqlsrv')->select("
                //     SELECT 
                //         KCU1.CONSTRAINT_NAME AS FK_CONSTRAINT_NAME,
                //         KCU1.TABLE_NAME AS FK_TABLE_NAME,
                //         KCU1.COLUMN_NAME AS FK_COLUMN_NAME,
                //         KCU2.TABLE_NAME AS REFERENCED_TABLE_NAME,
                //         KCU2.COLUMN_NAME AS REFERENCED_COLUMN_NAME
                //     FROM 
                //         INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS RC
                //         INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KCU1
                //             ON KCU1.CONSTRAINT_CATALOG = RC.CONSTRAINT_CATALOG
                //             AND KCU1.CONSTRAINT_SCHEMA = RC.CONSTRAINT_SCHEMA
                //             AND KCU1.CONSTRAINT_NAME = RC.CONSTRAINT_NAME
                //         INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KCU2
                //             ON KCU2.CONSTRAINT_CATALOG = RC.UNIQUE_CONSTRAINT_CATALOG
                //             AND KCU2.CONSTRAINT_SCHEMA = RC.UNIQUE_CONSTRAINT_SCHEMA
                //             AND KCU2.CONSTRAINT_NAME = RC.UNIQUE_CONSTRAINT_NAME
                //             AND KCU2.ORDINAL_POSITION = KCU1.ORDINAL_POSITION
                //     WHERE 
                //         KCU1.TABLE_NAME = ?", [$tableName]);

                // foreach ($foreignKeys as $fk) {
                //     $fkColumnType = DB::connection('mysql')->getSchemaBuilder()->getColumnType($tableName, $fk->FK_COLUMN_NAME);
                //     $referencedColumnType = DB::connection('mysql')->getSchemaBuilder()->getColumnType($fk->REFERENCED_TABLE_NAME, $fk->REFERENCED_COLUMN_NAME);

                //     if ($fkColumnType !== $referencedColumnType) {
                //         throw new \Exception("Data type mismatch between {$fk->FK_COLUMN_NAME} and {$fk->REFERENCED_COLUMN_NAME}");
                //     }

                //     // Lấy thông tin chỉ mục từ MSSQL
                //     $mssqlIndex = DB::connection('sqlsrv')->select("
                //         SELECT 
                //             i.name AS index_name,
                //             c.name AS column_name
                //         FROM 
                //             sys.indexes AS i
                //             INNER JOIN sys.index_columns AS ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
                //             INNER JOIN sys.columns AS c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
                //         WHERE 
                //             i.object_id = OBJECT_ID(?) AND c.name = ?", [$tableName, $fk->FK_COLUMN_NAME]);

                //     if (!empty($mssqlIndex)) {
                //         $existingIndex = DB::connection('mysql')->select(DB::raw("SHOW INDEX FROM {$tableName} WHERE Column_name = '{$fk->FK_COLUMN_NAME}'"));
                //         if (empty($existingIndex)) {
                //             Schema::connection('mysql')->table($tableName, function ($table) use ($fk) {
                //                 $table->index($fk->FK_COLUMN_NAME);
                //             });
                //         }
                //     }

                //     $referencedIndex = DB::connection('mysql')->select(DB::raw("SHOW INDEX FROM {$fk->REFERENCED_TABLE_NAME} WHERE Column_name = '{$fk->REFERENCED_COLUMN_NAME}'"));
                //     if (empty($referencedIndex)) {
                //         Schema::connection('mysql')->table($fk->REFERENCED_TABLE_NAME, function ($table) use ($fk) {
                //             $table->index($fk->REFERENCED_COLUMN_NAME);
                //         });
                //     }

                //     Schema::connection('mysql')->table($tableName, function ($table) use ($fk) {
                //         $table->foreign($fk->FK_COLUMN_NAME)
                //             ->references($fk->REFERENCED_COLUMN_NAME)
                //             ->on($fk->REFERENCED_TABLE_NAME)
                //             ->onDelete('cascade')
                //             ->onUpdate('cascade');
                //     });
                // }

                // Lấy thông tin ràng buộc mặc định từ MSSQL
                // $defaultConstraints = DB::connection('sqlsrv')->select("
                //     SELECT 
                //         c.name AS COLUMN_NAME,
                //         dc.name AS CONSTRAINT_NAME,
                //         dc.definition AS DEFAULT_VALUE
                //     FROM 
                //         sys.tables t
                //         INNER JOIN sys.default_constraints dc ON t.object_id = dc.parent_object_id
                //         INNER JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
                //     WHERE 
                //         t.name = ?", [$tableName]);

                // foreach ($defaultConstraints as $dc) {
                //     Schema::connection('mysql')->table($tableName, function ($table) use ($dc) {
                //         $table->string($dc->COLUMN_NAME)->default(DB::raw($dc->DEFAULT_VALUE))->change();
                //     });
                // }

                // Lấy thông tin chỉ mục từ MSSQL
                // $indexes = DB::connection('sqlsrv')->select("
                //     SELECT 
                //         i.name AS index_name,
                //         c.name AS column_name
                //     FROM 
                //         sys.indexes AS i
                //         INNER JOIN sys.index_columns AS ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
                //         INNER JOIN sys.columns AS c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
                //     WHERE 
                //         i.object_id = OBJECT_ID(?)", [$tableName]);

                // foreach ($indexes as $index) {
                //     $existingIndex = DB::connection('mysql')->select(DB::raw("SHOW INDEX FROM {$tableName} WHERE Key_name = '{$index->index_name}'"));
                //     if (empty($existingIndex)) {
                //         Schema::connection('mysql')->table($tableName, function ($table) use ($index) {
                //             $table->index($index->column_name, $index->index_name);
                //         });
                //     }
                // }

                dump("Constraints added successfully to table {$tableName}.");
                // dd($indexes);
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

        dump("Table constraints added successfully.");
    }
}
