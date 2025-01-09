<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MigrateViewHandler;
use App\Services\MigrateTableWithOffsetLimit;
use App\Services\MigratePrimaryKeyAndIndexHandler;
use App\Services\AddForeignKeyHandler;
use App\Services\AutoIncrementPk;

class MigrateDatabase extends Command
{
    protected $signature = 'migrate:databases {task}';
    protected $description = 'Migrate specific part of the database from MSSQL to MySQL';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $task = $this->argument('task');

        switch ($task) {
            case 'tables':
                MigrateTableWithOffsetLimit::migrateTables();
                $this->info('Tables migrated successfully.');
                break;

            case 'primary-key-index':
                MigratePrimaryKeyAndIndexHandler::addPrimaryKeyAndIndex();
                $this->info('Primary keys and indexes migrated successfully.');
                break;

            case 'foreign-keys':
                AddForeignKeyHandler::addForeignKey();
                $this->info('Foreign keys added successfully.');
                break;

            case 'auto-increment-pk':
                AutoIncrementPk::migrateTables();
                $this->info('Auto increment primary keys migrated successfully.');
                break;

            case 'views':
                MigrateViewHandler::migrateViews();
                $this->info('Views migrated successfully.');
                break;

            default:
                $this->error('Invalid task specified.');
                break;
        }
    }
}
