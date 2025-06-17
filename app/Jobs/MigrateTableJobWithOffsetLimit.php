<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class MigrateTableJobWithOffsetLimit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Retry 3 times
    public $timeout = 300; // Timeout after 5 minutes

    protected $tableName;
    protected $startId;
    protected $endId;
    protected $chunkSize;
    protected $jobId;

    public function __construct($tableName, $startId, $endId, $chunkSize)
    {
        $this->tableName = $tableName;
        $this->startId = $startId;
        $this->endId = $endId;
        $this->chunkSize = $chunkSize;
        $this->jobId = uniqid();

        DB::connection('mysql')->table('job_statuses')->insert([
            'job_id' => $this->jobId,
            'table_name' => $this->tableName,
            'start_id' => $this->startId,
            'end_id' => $this->endId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function handle()
    {
        $startTime = microtime(true);
        $tableName = $this->tableName;
        $startId = $this->startId;
        $endId = $this->endId;
        $chunkSize = $this->chunkSize;

        try {
            // Kiểm tra schema trước khi migrate
            $mssqlColumns = Schema::connection('sqlsrv')->getColumnListing($tableName);
            $mysqlColumns = Schema::connection('mysql')->getColumnListing($tableName);

            if (array_diff($mssqlColumns, $mysqlColumns) || array_diff($mysqlColumns, $mssqlColumns)) {
                throw new \Exception("Schema mismatch between MSSQL and MySQL for table {$tableName}.");
            }

            $idColumnExists = Schema::connection('sqlsrv')->hasColumn($tableName, 'id');
            $idColumnType = null;

            if ($idColumnExists) {
                $idColumnType = DB::connection('sqlsrv')
                    ->select("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = 'id'", [$tableName]);

                $idColumnType = !empty($idColumnType) ? $idColumnType[0]->DATA_TYPE : null;
            }

            $recordsProcessed = 0;

            if (!$idColumnExists || !in_array($idColumnType, ['int', 'bigint'])) {
                $columns = Schema::connection('sqlsrv')->getColumnListing($tableName);
                $firstColumn = $columns[0] ?? null;
                $secondColumn = $columns[1] ?? null;

                $query = DB::connection('sqlsrv')->table($tableName);

                if ($firstColumn) {
                    $query->orderBy($firstColumn);
                }
                if ($secondColumn) {
                    $query->orderBy($secondColumn);
                }

                // Sử dụng cursor thay vì chunk để giảm tải bộ nhớ
                foreach ($query->cursor() as $row) {
                    DB::connection('mysql')->table($tableName)->insert((array) $row);
                    $recordsProcessed++;
                }
            } else {
                $query = DB::connection('sqlsrv')
                    ->table($tableName)
                    ->whereBetween('id', [$startId, $endId])
                    ->orderBy('id');

                foreach ($query->cursor() as $row) {
                    DB::connection('mysql')->table($tableName)->insert((array) $row);
                    $recordsProcessed++;
                }
            }

            $executionTime = microtime(true) - $startTime;
            Log::info("Data chunk for table {$tableName} from ID {$startId} to {$endId} migrated successfully. Processed {$recordsProcessed} records in {$executionTime} seconds.");

            DB::connection('mysql')->table('job_statuses')
                ->where('job_id', $this->jobId)
                ->update([
                    'completed' => true,
                    'records_processed' => $recordsProcessed,
                    'execution_time' => $executionTime,
                    'updated_at' => now(),
                ]);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $stackTrace = $e->getTraceAsString();
            Log::error("Error migrating chunk for table {$tableName} from ID {$startId} to {$endId}: {$errorMessage}\nStack Trace: {$stackTrace}");

            DB::connection('mysql')->table('job_statuses')
                ->where('job_id', $this->jobId)
                ->update([
                    'completed' => false,
                    'error_message' => $errorMessage,
                    'stack_trace' => $stackTrace,
                    'updated_at' => now(),
                ]);

            throw $e; // Ném lại lỗi để queue xử lý retry
        }
    }
}