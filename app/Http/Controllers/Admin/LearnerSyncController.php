<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\LearnerSyncJob;
use App\Models\Learner;
use App\Models\LearnerSyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class LearnerSyncController extends Controller
{
    /**
     * Display the Learner Synchronization dashboard.
     */
    public function index()
    {
        return view('admin.learner-sync');
    }



    /**
     * Return synchronization dashboard statistics and recent runs.
     */
    public function data(): JsonResponse
    {
        // Fetch the latest synchronization run.
        $latestRun = LearnerSyncLog::query()
            ->latest('id')
            ->first();

        // Consider a run active only when the latest run is still running.
        $activeRun = $latestRun && $latestRun->status === 'running'
            ? $latestRun
            : null;

        // Fetch the latest synchronization history.
        $runs = LearnerSyncLog::query()
            ->latest('id')
            ->paginate(10);

        return response()->json([
            'success' => true,

            'summary' => [
                'total_learners' => Learner::count(),
                'total_runs' => LearnerSyncLog::count(),
                'completed_runs' => LearnerSyncLog::where('status', 'completed')->count(),
                'failed_runs' => LearnerSyncLog::where('status', 'failed')->count(),
            ],

            'active_run' => $activeRun,

            'latest_run' => $latestRun,

            'runs' => $runs,
        ]);
    }


    /**
     * Create a synchronization log and dispatch its background job.
     */
    public function run(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sync_type' => ['required', 'in:full,incremental'],
        ]);

        $syncType = $validated['sync_type'];
        $fullSync = $syncType === 'full';
        $runId = (string) Str::uuid();
        $lockKey = 'learner_sync_active_run';

        // Remove a stale lock only when its associated run is no longer running.
        $existingLockRunId = Cache::get($lockKey);

        if ($existingLockRunId) {
            $existingRun = LearnerSyncLog::where('run_id', $existingLockRunId)->first();

            if (!$existingRun || $existingRun->status !== 'running') {
                Cache::forget($lockKey);
            }
        }

        // Atomically prevent two administrators from starting simultaneous sync jobs.
        if (!Cache::add($lockKey, $runId, 21720)) {
            return response()->json([
                'success' => false,
                'message' => 'A learner synchronization is already running. Please wait until it finishes.',
            ], 409);
        }

        try {
            $syncLog = DB::transaction(function () use ($runId, $syncType) {
                return LearnerSyncLog::create([
                    'run_id' => $runId,
                    'sync_type' => $syncType,
                    'status' => 'running',
                    'started_at' => now(),
                    'total_fetched' => 0,
                    'total_inserted' => 0,
                    'total_updated' => 0,
                    'total_unchanged' => 0,
                    'total_duplicates' => 0,
                    'total_failed' => 0,
                    'total_processed' => 0,
                    'total_success' => 0,
                    'total_pages' => 0,
                ]);
            });

            LearnerSyncJob::dispatch($runId, $fullSync);

            return response()->json([
                'success' => true,
                'message' => ucfirst($syncType) . ' synchronization has been queued successfully.',
                'run' => $syncLog,
            ], 202);

        } catch (Throwable $exception) {
            // Release the lock if log creation or job dispatch fails.
            if (Cache::get($lockKey) === $runId) {
                Cache::forget($lockKey);
            }

            LearnerSyncLog::where('run_id', $runId)
                ->where('status', 'running')
                ->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => $exception->getMessage(),
                ]);

            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to start learner synchronization.',
            ], 500);
        }
    }

    /**
     * Return the latest status and metrics for one synchronization run.
     */
    public function status(LearnerSyncLog $syncLog): JsonResponse
    {
        return response()->json([
            'success' => true,
            'run' => $syncLog->fresh(),
        ]);
    }

    /**
     * Return paginated page-by-page synchronization history.
     */
    public function pages(LearnerSyncLog $syncLog): JsonResponse
    {
        $pages = $syncLog->pages()
            ->orderBy('page_number')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'pages' => $pages,
        ]);
    }

    /**
     * Return paginated duplicate, ambiguous, or failed learner records.
     */
    public function records(Request $request, LearnerSyncLog $syncLog): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:duplicate,ambiguous,failed'],
            'page_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = $syncLog->recordLogs()->latest('id');

        if (!empty($validated['type'])) {
            $query->where('record_type', $validated['type']);
        }

        if (!empty($validated['page_number'])) {
            $query->where('page_number', $validated['page_number']);
        }

        return response()->json([
            'success' => true,
            'records' => $query->paginate(20),
        ]);
    }
}
