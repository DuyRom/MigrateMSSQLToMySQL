<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MigrateProcedureHandler;

class MigrateProcedures extends Command
{
    protected $signature = 'migrate:procedures';
    protected $description = 'Migrate stored procedures from MSSQL to MySQL';

    protected $migrateProcedureHandler;

    public function __construct(MigrateProcedureHandler $migrateProcedureHandler)
    {
        parent::__construct();
        $this->migrateProcedureHandler = $migrateProcedureHandler;
    }

    public function handle()
    {
        $this->info('Starting migration of stored procedures...');
        $this->migrateProcedureHandler->migrateProcedures();
        $this->info('Migration completed.');
    }
}
