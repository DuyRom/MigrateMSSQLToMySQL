<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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
       
            if (!Schema::connection('sqlsrv')->hasColumn($tableName, 'id')) {
                dump("Table {$tableName} does not have an 'id' column.");
                \Log::error("Table {$tableName} does not have an 'id' column.");
                return;
            }

            $rows = DB::connection('sqlsrv')
                ->table($tableName)
                ->whereBetween('id', [$startId, $endId])
                ->orderBy('id')
                ->limit($chunkSize)
                ->get();

            if ($rows->isEmpty()) {
                return;
            }

            foreach ($rows as $row) {
                DB::connection('mysql')->table($tableName)->insert((array) $row);
            }

            dump("Data chunk for table {$tableName} from ID {$startId} to {$endId} migrated successfully.");

        } catch (\Exception $e) {
            dump("Error migrating chunk for table {$tableName} from ID {$startId} to {$endId}. ");
            \Log::error("Error migrating chunk for table {$tableName} from ID {$startId} to {$endId}: " . $e->getMessage());
        }
    }
}
