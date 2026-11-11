<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncEventTransactionFieldAgentData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 1;

    public function handle(): void
    {
        // Use the configured connection for the uat_yuthhub_workspace database.
        $connection = DB::connection('mysql');

        $before = $connection->selectOne("
            SELECT COUNT(*) AS total
            FROM uat_yuthhub_workspace.event_transactions et
            INNER JOIN uat_yuthhub_workspace.field_agents fa
                ON et.field_agent_id = fa.id
            WHERE NOT (et.partner_id <=> fa.partner_id)
               OR NOT (et.partner_center_id <=> fa.partner_center_id)
        ");

        $pending = (int) $before->total;

        Log::info('Event transaction sync started', [
            'records_pending' => $pending,
            'connection' => 'mysql',
        ]);

        $updated = $connection->affectingStatement("
            UPDATE uat_yuthhub_workspace.event_transactions AS et
            INNER JOIN uat_yuthhub_workspace.field_agents AS fa
                ON et.field_agent_id = fa.id
            SET
                et.partner_id = fa.partner_id,
                et.partner_center_id = fa.partner_center_id
            WHERE NOT (et.partner_id <=> fa.partner_id)
               OR NOT (et.partner_center_id <=> fa.partner_center_id)
        ");

        $remaining = $connection->selectOne("
            SELECT COUNT(*) AS total
            FROM uat_yuthhub_workspace.event_transactions et
            INNER JOIN uat_yuthhub_workspace.field_agents fa
                ON et.field_agent_id = fa.id
            WHERE NOT (et.partner_id <=> fa.partner_id)
               OR NOT (et.partner_center_id <=> fa.partner_center_id)
        ");

        Log::info('Event transaction sync completed', [
            'records_pending_before' => $pending,
            'affected_rows' => $updated,
            'records_remaining' => (int) $remaining->total,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Event transaction sync failed', [
            'message' => $exception?->getMessage(),
        ]);
    }
}
