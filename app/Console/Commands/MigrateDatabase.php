<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MigrateViewHandler;
use App\Services\MigrateTableHandler;
use App\Services\MigrateProcedureHandler;
use App\Services\AddTableConstraintHandler;
use App\Services\MigrateTableWithOffsetLimit;
use App\Services\MigratePrimaryKeyAndIndexHandler;
use App\Services\AddForeignKeyHandler;

class MigrateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:databases';

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
        //MigrateTableWithOffsetLimit::migrateTables();
        //MigratePrimaryKeyAndIndexHandler::addPrimaryKeyAndIndex();
        AddForeignKeyHandler::addForeignKey();
        // MigrateViewHandler::migrateViews();
        // MigrateProcedureHandler::create();
        $this->info('Database migration completed successfully.');
    }

    

   
}
