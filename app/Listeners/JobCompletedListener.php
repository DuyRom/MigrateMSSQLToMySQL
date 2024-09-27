<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\MigrationCompleted;


class JobCompletedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
         $pendingJobs = DB::table('job_statuses')->where('completed', false)->count();

         if ($pendingJobs === 0) {
             $successfulMigrations = DB::table('migration_status')->where('migration_success', true)->pluck('table_name')->toArray();
             $failedMigrations = DB::table('migration_status')->where('migration_success', false)->pluck('table_name')->toArray();
 
             Mail::to(config('mail.to.address'))->send(new MigrationCompleted($successfulMigrations, $failedMigrations));
         }
    }
}
