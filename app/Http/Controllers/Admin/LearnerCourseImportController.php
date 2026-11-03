<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLearnerCourseImport;
use App\Models\LearnerCourseImport;
use App\Models\LearnerCourseImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LearnerCourseImportController extends Controller
{
    /**
     * Display the Learner Course CSV Import dashboard.
     */
    public function index()
    {
        return view('admin.learner-course-import');
    }

    /**
     * Upload a CSV file, create its import batch, and dispatch processing.
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:51200',
            ],
        ]);

        $file = $validated['file'];
        $batchId = (string) Str::uuid();
        $filePath = null;

        try {
            // Store the original CSV privately on the local storage disk.
            $filePath = $file->store(
                'learner-course-imports',
                'local'
            );

            if (!$filePath) {
                throw new \RuntimeException(
                    'Unable to store the uploaded CSV file.'
                );
            }

            // Create a tracking record for this CSV upload.
            $batch = LearnerCourseImportBatch::create([
                'batch_id' => $batchId,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'status' => 'queued',
                'total_rows' => 0,
                'processed_rows' => 0,
                'matched_rows' => 0,
                'pending_rows' => 0,
                'skipped_rows' => 0,
                'failed_rows' => 0,
                'invalid_file_path' => null,
            ]);

            // Dispatch CSV processing to the dedicated learner-import queue.
            ProcessLearnerCourseImport::dispatch($batchId);

            return response()->json([
                'success' => true,
                'message' => 'CSV uploaded successfully. Import processing has been queued.',
                'batch' => $batch,
            ], 202);

        } catch (Throwable $exception) {
            // Remove the stored CSV if batch creation or job dispatch fails.
            if ($filePath && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to upload or queue the CSV import.',
            ], 500);
        }
    }

    /**
     * Return import dashboard statistics and paginated batch history.
     */
    public function data(): JsonResponse
    {
        // Fetch the latest import batch.
        $latestBatch = LearnerCourseImportBatch::query()
            ->latest('id')
            ->first();

        // Identify a currently processing batch.
        $activeBatch = LearnerCourseImportBatch::query()
            ->whereIn('status', ['queued', 'processing'])
            ->latest('id')
            ->first();

        // Fetch paginated import history.
        $batches = LearnerCourseImportBatch::query()
            ->latest('id')
            ->paginate(10);

        return response()->json([
            'success' => true,

            'summary' => [
                'total_imports' => LearnerCourseImportBatch::count(),

                'completed_imports' => LearnerCourseImportBatch::where(
                    'status',
                    'completed'
                )->count(),

                'failed_imports' => LearnerCourseImportBatch::where(
                    'status',
                    'failed'
                )->count(),

                'total_records' => LearnerCourseImportBatch::sum('total_rows'),

                'total_processed' => LearnerCourseImportBatch::sum('processed_rows'),

                'total_matched' => LearnerCourseImportBatch::sum('matched_rows'),

                'total_pending' => LearnerCourseImportBatch::sum('pending_rows'),

                'total_skipped' => LearnerCourseImportBatch::sum('skipped_rows'),
            ],

            'active_batch' => $activeBatch,

            'latest_batch' => $latestBatch,

            'batches' => $batches,
        ]);
    }

    /**
     * Return the latest status and progress of a specific import batch.
     */
    public function status(string $batchId): JsonResponse
    {
        $batch = LearnerCourseImportBatch::where(
            'batch_id',
            $batchId
        )->firstOrFail();

        return response()->json([
            'success' => true,
            'batch' => $batch->fresh(),
        ]);
    }

    /**
     * Download the invalid contacts CSV generated for a specific import batch.
     */
    public function downloadInvalidContacts(string $batchId): StreamedResponse|JsonResponse
    {
        $batch = LearnerCourseImportBatch::where(
            'batch_id',
            $batchId
        )->firstOrFail();

        // Verify that the batch contains skipped contacts and a generated file.
        if (
            (int) $batch->skipped_rows <= 0 ||
            empty($batch->invalid_file_path) ||
            !Storage::disk('local')->exists($batch->invalid_file_path)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No invalid contacts file is available for this batch.',
            ], 404);
        }

        // Generate a safe download filename from the original CSV filename.
        $filename = pathinfo(
            $batch->original_filename,
            PATHINFO_FILENAME
        ) . '-invalid-contacts.csv';

        // Download the privately stored invalid contacts CSV.
        return Storage::disk('local')->download(
            $batch->invalid_file_path,
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    /**
     * Return paginated imported records with optional matching status filters.
     */
    public function records(Request $request, string $batchId): JsonResponse
    {
        $validated = $request->validate([
            'match_status' => ['nullable', 'in:all,pending,matched'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $batch = LearnerCourseImportBatch::where(
            'batch_id',
            $batchId
        )->firstOrFail();

        // Retrieve CSV records belonging only to the selected batch.
        $query = LearnerCourseImport::query()
            ->where('import_batch_id', $batch->batch_id)
            ->with('learner')
            ->latest('id');

        // Apply match status filtering only when a specific status is selected.
        if (
            isset($validated['match_status']) &&
            $validated['match_status'] !== 'all'
        ) {
            $query->where('match_status', $validated['match_status']);
        }

        // Search imported records by contact, name, or course.
        if (!empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($subQuery) use ($search): void {
                $subQuery
                    ->where('contact', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('course_name', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'batch' => $batch,
            'records' => $query->paginate(
                $validated['per_page'] ?? 20
            ),
        ]);
    }
}
