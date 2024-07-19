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

    public function migrateProcedures()
    {
        // Lấy danh sách các thủ tục lưu trữ từ MSSQL
        $procedures = DB::connection('sqlsrv')->select("
            SELECT SPECIFIC_NAME, ROUTINE_DEFINITION 
            FROM INFORMATION_SCHEMA.ROUTINES 
            WHERE ROUTINE_TYPE = 'PROCEDURE'
        ");

        // Lấy danh sách các thủ tục lưu trữ đã tồn tại trong MySQL
        $existingProcedures = DB::connection('mysql')->select("
            SELECT ROUTINE_NAME 
            FROM INFORMATION_SCHEMA.ROUTINES 
            WHERE ROUTINE_TYPE = 'PROCEDURE' AND ROUTINE_SCHEMA = DATABASE()
        ");

        // Tạo một mảng các tên thủ tục lưu trữ đã tồn tại
        $existingProcedureNames = array_map(function ($procedure) {
            return $procedure->ROUTINE_NAME;
        }, $existingProcedures);

        foreach ($procedures as $procedure) {
            $procedureName = $procedure->SPECIFIC_NAME;

            // Kiểm tra xem thủ tục lưu trữ đã tồn tại chưa
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
