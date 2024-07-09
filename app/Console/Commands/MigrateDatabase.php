<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Schema;


class MigrateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate entire database from MSSQL to MySQL';

    /**
     * Execute the console command.
     *
     * @return int
     */

     public function __construct()
     {
         parent::__construct();
     }

     public function handle()
    {
        $this->migrateTables();
        $this->migrateViews();
        $this->migrateStoredProcedures();

        $this->info('Database migration completed successfully.');
    }

    private function migrateTables()
    {
        // Lấy danh sách các bảng từ MSSQL Server
        $tables = DB::connection('sqlsrv')->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");

        foreach ($tables as $table) {
            $tableName = $table->TABLE_NAME;

            try {
                // Lấy cấu trúc bảng từ MSSQL Server
                $columns = DB::connection('sqlsrv')->select("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?", [$tableName]);

                // Kiểm tra xem bảng có tồn tại trong MySQL hay không
                if (!Schema::connection('mysql')->hasTable($tableName)) {
                    // Tạo bảng tương ứng trong MySQL
                    Schema::connection('mysql')->create($tableName, function ($table) use ($columns) {
                        foreach ($columns as $column) {
                            $columnName = $column->COLUMN_NAME;
                            $dataType = $column->DATA_TYPE;
                            $isNullable = $column->IS_NULLABLE === 'YES';
                            $maxLength = $column->CHARACTER_MAXIMUM_LENGTH;

                            // Chuyển đổi kiểu dữ liệu từ MSSQL sang MySQL
                            switch ($dataType) {
                                case 'int':
                                    $table->integer($columnName)->nullable($isNullable);
                                    break;
                                case 'varchar':
                                    $table->string($columnName, $maxLength)->nullable($isNullable);
                                    break;
                                case 'text':
                                    $table->text($columnName)->nullable($isNullable);
                                    break;
                                case 'datetime':
                                    $table->dateTime($columnName)->nullable($isNullable);
                                    break;
                                case 'bit':
                                    $table->boolean($columnName)->nullable($isNullable);
                                    break;
                                // Thêm các kiểu dữ liệu khác nếu cần
                                default:
                                    $table->string($columnName)->nullable($isNullable);
                                    break;
                            }
                        }
                    });

                    $this->info("Table {$tableName} created successfully in MySQL.");
                } else {
                    $this->info("Table {$tableName} already exists in MySQL.");
                }

                // Truncate bảng trước khi di chuyển dữ liệu
                DB::connection('mysql')->table($tableName)->truncate();

                // Di chuyển dữ liệu từ MSSQL Server sang MySQL
                $data = DB::connection('sqlsrv')->table($tableName)->get();
                foreach ($data as $row) {
                    DB::connection('mysql')->table($tableName)->insert((array) $row);
                }
                $this->info("Data of table {$tableName} migrated successfully.");
            } catch (\Exception $e) {
                // Ghi log lỗi và tiếp tục với bảng tiếp theo
                \Log::error("Error migrating table {$tableName}: " . $e->getMessage());
                $this->error("Error migrating table {$tableName}. Check log for details.");
                continue;
            }
        }
    }


    private function migrateViews()
    {
        // Lấy danh sách các view từ MSSQL Server
        $views = DB::connection('sqlsrv')->select('SELECT TABLE_NAME, VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS');

        foreach ($views as $view) {
            $viewName = $view->TABLE_NAME;
            $viewDefinition = $view->VIEW_DEFINITION;

            // Tạo view tương ứng trong MySQL
            DB::connection('mysql')->statement("CREATE VIEW {$viewName} AS {$viewDefinition}");

            $this->info("View {$viewName} created successfully in MySQL.");
        }
    }

    private function migrateStoredProcedures()
    {
        // Lấy danh sách các stored procedure từ MSSQL Server
        $procedures = DB::connection('sqlsrv')->select("SELECT SPECIFIC_NAME, ROUTINE_DEFINITION FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_TYPE = 'PROCEDURE'");

        foreach ($procedures as $procedure) {
            $procedureName = $procedure->SPECIFIC_NAME;
            $procedureDefinition = $procedure->ROUTINE_DEFINITION;

            // Tạo stored procedure tương ứng trong MySQL
            DB::connection('mysql')->unprepared("DROP PROCEDURE IF EXISTS {$procedureName};");
            DB::connection('mysql')->unprepared("CREATE PROCEDURE {$procedureName} {$procedureDefinition}");

            $this->info("Stored Procedure {$procedureName} created successfully in MySQL.");
        }
    }

}
