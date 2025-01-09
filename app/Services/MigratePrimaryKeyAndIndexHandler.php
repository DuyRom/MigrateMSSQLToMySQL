<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigratePrimaryKeyAndIndexHandler 
{
    public static function addPrimaryKeyAndIndex()
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
                $primaryKeys = DB::connection('sqlsrv')->select("
                    SELECT COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE OBJECTPROPERTY(OBJECT_ID(CONSTRAINT_SCHEMA + '.' + CONSTRAINT_NAME), 'IsPrimaryKey') = 1 
                    AND TABLE_NAME = ?", [$tableName]);

                if (!empty($primaryKeys)) {
                    $primaryKeyColumns = array_map(function ($pk) {
                        return $pk->COLUMN_NAME;
                    }, $primaryKeys);

                    // Kiểm tra các cột hiện tại của bảng trong MySQL
                    $mysqlColumns = DB::connection('mysql')->select("SHOW COLUMNS FROM {$tableName}");

                    // Lọc ra các cột chưa phải là khóa chính
                    $columnsToSetAsPrimary = array_filter($primaryKeyColumns, function ($column) use ($mysqlColumns) {
                        foreach ($mysqlColumns as $mysqlColumn) {
                            if ($mysqlColumn->Field == $column && $mysqlColumn->Key == 'PRI') {
                                return false;
                            }
                        }
                        return true;
                    });

                    if (!empty($columnsToSetAsPrimary)) {
                        // Kiểm tra xem có giá trị trùng lặp trong các cột này không
                        $duplicateCheckQuery = "SELECT COUNT(*) as count, " . implode(", ", $columnsToSetAsPrimary) . " FROM {$tableName} GROUP BY " . implode(", ", $columnsToSetAsPrimary) . " HAVING count > 1";
                        $duplicates = DB::connection('mysql')->select(DB::raw($duplicateCheckQuery));

                        if (empty($duplicates)) {
                            Schema::connection('mysql')->table($tableName, function ($table) use ($columnsToSetAsPrimary) {
                                $table->primary($columnsToSetAsPrimary);
                            });
                        } else {
                            throw new \Exception("Duplicate entries found for columns: " . implode(", ", $columnsToSetAsPrimary));
                        }
                    }
                }

                // Lấy thông tin chỉ mục từ MSSQL
                $indexes = DB::connection('sqlsrv')->select("
                    SELECT 
                        i.name AS index_name,
                        c.name AS column_name
                    FROM 
                        sys.indexes AS i
                        INNER JOIN sys.index_columns AS ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
                        INNER JOIN sys.columns AS c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
                    WHERE 
                        i.object_id = OBJECT_ID(?)", [$tableName]);
                
                foreach ($indexes as $index) {
                    $existingIndex = DB::connection('mysql')->select(DB::raw("SHOW INDEX FROM {$tableName} WHERE Key_name = '{$index->index_name}'"));
                    if (empty($existingIndex)) {
                        Schema::connection('mysql')->table($tableName, function ($table) use ($index) {
                            $table->index($index->column_name, $index->index_name);
                        });
                    }
                }

                dump("Constraints added successfully to table {$tableName}.");
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

        dump("Migration of primary keys and indexes completed.");
    }
}
