<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSysVariables extends Command
{
    protected $signature = 'migrate:SysVariables';

    protected $description = 'Migrate SysVariables table from SQL Server to MySQL';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $this->info('Starting migration of SysVariables table...');

            $rows = DB::connection('sqlsrv')->table('SysVariables')->get();

            foreach ($rows as $row) {
                DB::connection('mysql')->table('SysVariables')->insert((array) $row);
            }

            $this->info('Migration of SysVariables table completed successfully.');
        } catch (\Exception $e) {
            $this->error('Error migrating SysVariables table: ' . $e->getMessage());
            \Log::error('Error migrating SysVariables table: ' . $e->getMessage());
        }
    }
}
