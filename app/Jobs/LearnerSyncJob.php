<?php

namespace App\Jobs;

use App\Models\LearnerSyncLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

class LearnerSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum execution time for a complete synchronization run.
     */
    public int $timeout = 21600;

    /**
     * Allow only one execution attempt to avoid duplicate synchronization.
     */
    public int $tries = 1;

    /**
     * Create a new learner synchronization job.
     */
    public function __construct(
        public string $runId,
        public bool $fullSync = false
    ) {
        $this->onQueue('learner-sync');
    }

   /**
   *  Execute the existing Artisan learner synchronization command.
   */
    public function handle(): void
    {
        try {
            $syncLog = LearnerSyncLog::where('run_id', $this->runId)->firstOrFail();

            if ($syncLog->status !== 'running') {
                return;
            }

            $arguments = [
                '--run-id' => $this->runId,
            ];

            if ($this->fullSync) {
                $arguments['--full'] = true;
            }

            $exitCode = Artisan::call('learners:sync', $arguments);

            if ($exitCode !== Artisan::SUCCESS) {
                $syncLog->refresh();

                if ($syncLog->status === 'running') {
                    $syncLog->update([
                        'status' => 'failed',
                        'completed_at' => now(),
                        'error_message' => 'Learner synchronization command returned a failure status.',
                    ]);
                }

                throw new \RuntimeException(
                    'Learner synchronization failed. Check the synchronization log for details.'
                );
            }
        } finally {
            // Release the synchronization lock only when it belongs to this run.
            if (Cache::get('learner_sync_active_run') === $this->runId) {
                Cache::forget('learner_sync_active_run');
            }
        }
    }

    /**
     * Mark the synchronization as failed if the queued job fails.
     */
    public function failed(Throwable $exception): void
    {
        LearnerSyncLog::where('run_id', $this->runId)
            ->where('status', 'running')
            ->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

        Log::error('Learner synchronization queue job failed.', [
            'run_id' => $this->runId,
            'error' => $exception->getMessage(),
        ]);

        // Release the lock when the queued job fails.
        if (Cache::get('learner_sync_active_run') === $this->runId) {
            Cache::forget('learner_sync_active_run');
        }
    }
}
