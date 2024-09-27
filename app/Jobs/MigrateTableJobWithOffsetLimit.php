<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateTableJobWithOffsetLimit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tableName;
    protected $startId;
    protected $endId;
    protected $chunkSize;

    protected $jobId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $tableName = $this->tableName;
        $startId = $this->startId;
        $endId = $this->endId;
        $chunkSize = $this->chunkSize;

        try {
            $idColumnExists = Schema::connection('sqlsrv')->hasColumn($tableName, 'id');
            $idColumnType = null;

            if ($idColumnExists) {
                $idColumnType = DB::connection('sqlsrv')
                    ->select("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = 'id'", [$tableName]);

                if (!empty($idColumnType)) {
                    $idColumnType = $idColumnType[0]->DATA_TYPE;
                }
            }

            if (!$idColumnExists || !in_array($idColumnType, ['int', 'bigint'])) {
                DB::connection('sqlsrv')
                    ->table($tableName)
                    ->chunk($chunkSize, function ($rows) use ($tableName) {
                        foreach ($rows as $row) {
                            DB::connection('mysql')->table($tableName)->insert((array) $row);
                        }
                    });
            } else {
                DB::connection('sqlsrv')
                    ->table($tableName)
                    ->whereBetween('id', [$startId, $endId])
                    ->orderBy('id')
                    ->chunk($chunkSize, function ($rows) use ($tableName) {
                        foreach ($rows as $row) {
                            DB::connection('mysql')->table($tableName)->insert((array) $row);
                        }
                    });
            }

            dump("Data chunk for table {$tableName} from ID {$startId} to {$endId} migrated successfully.");

        } catch (\Exception $e) {
            dump("Error migrating chunk for table {$tableName} from ID {$startId} to {$endId}. ");
            \Log::error("Error migrating chunk for table {$tableName} from ID {$startId} to {$endId}: " . $e->getMessage());
        }

        DB::connection('mysql')->table('job_statuses')
            ->where('job_id', $this->jobId)
            ->update([
                'completed' => true,
                'updated_at' => now(),
            ]);
    }
}
