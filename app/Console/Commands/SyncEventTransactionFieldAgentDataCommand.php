<?php

namespace App\Console\Commands;

use App\Jobs\SyncEventTransactionFieldAgentData;
use Illuminate\Console\Command;
use Throwable;

class SyncEventTransactionFieldAgentDataCommand extends Command
{
    protected $signature = 'event-transactions:sync-field-agent-data';

    protected $description =
        'Sync partner IDs and partner center IDs from field agents';

    public function handle(): int
    {
        try {
            $this->info('Starting event transaction sync...');

            SyncEventTransactionFieldAgentData::dispatchSync();

            $this->info('Sync job executed. Check the Laravel log for results.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);

            $this->error('Sync failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
