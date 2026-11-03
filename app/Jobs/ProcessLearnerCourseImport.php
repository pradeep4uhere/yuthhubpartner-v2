<?php

namespace App\Jobs;

use App\Models\Learner;
use App\Models\LearnerCourseImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessLearnerCourseImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 21600;

    public int $tries = 1;

    /**
     * Initialize the CSV import job with its batch identifier.
     */
    public function __construct(
        public string $batchId
    ) {
        $this->onQueue('learner-import');
    }

    /**
     * Process valid mobile contacts and export invalid contact rows separately.
     */
    public function handle(): void
    {
        $batch = LearnerCourseImportBatch::where(
            'batch_id',
            $this->batchId
        )->firstOrFail();

        $filePath = Storage::disk('local')->path($batch->file_path);

        if (!is_file($filePath)) {
            throw new \RuntimeException('Uploaded CSV file was not found.');
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open the uploaded CSV file.');
        }

        $invalidHandle = null;
        $invalidFilePath = null;

        try {
            // Read the original CSV headers.
            $headers = fgetcsv($handle, 0, ',', '"', '');

            if ($headers === false) {
                throw new \RuntimeException('The uploaded CSV file is empty.');
            }

            // Build a normalized header-to-column-index mapping.
            $headerIndexes = [];

            foreach ($headers as $index => $header) {
                $normalizedHeader = $this->normalizeHeader((string) $header);

                if ($normalizedHeader !== '') {
                    $headerIndexes[$normalizedHeader] = $index;
                }
            }

            // Accept both supported data-sharing consent headers.
            $consentHeaderAliases = [
                'data sharing consent',
                'do you declare that you allow the team to share your data for monitoring and providing more support?',
            ];

            // Define the required CSV headers.
            $requiredHeaders = [
                'Course Name',
                'Contact',
                'First Name',
                'Last Name',
                'My groups',
                'Age (In Years)',
                'Gender',
                'State',
                'Course Completion',
            ];

            // Identify missing required headers.
            $missingHeaders = [];

            foreach ($requiredHeaders as $requiredHeader) {
                if (!array_key_exists(
                    $this->normalizeHeader($requiredHeader),
                    $headerIndexes
                )) {
                    $missingHeaders[] = $requiredHeader;
                }
            }

            // Verify that one supported consent header exists.
            $hasConsentHeader = false;

            foreach ($consentHeaderAliases as $consentHeader) {
                if (array_key_exists(
                    $this->normalizeHeader($consentHeader),
                    $headerIndexes
                )) {
                    $hasConsentHeader = true;
                    break;
                }
            }

            if (!$hasConsentHeader) {
                $missingHeaders[] = 'Data Sharing Consent';
            }

            if (!empty($missingHeaders)) {
                throw new \RuntimeException(
                    'Missing CSV columns: '
                    . implode(', ', $missingHeaders)
                    . '. Actual CSV headers: '
                    . implode(' | ', array_map(
                        fn ($header) => (string) $header,
                        $headers
                    ))
                );
            }

            // Prevent accidental duplicate imports for an existing batch.
            $existingRecords = DB::table('learner_course_imports')
                ->where('import_batch_id', $batch->batch_id)
                ->exists();

            if ($existingRecords) {
                throw new \RuntimeException(
                    'This batch already contains imported records. '
                    . 'Do not retry this batch. Create a new batch or '
                    . 'safely recover the existing batch.'
                );
            }

            // Count all non-empty input rows, including invalid contacts.
            $totalCsvRows = $this->countCsvRows($filePath);

            // Reset batch counters before processing.
            $batch->update([
                'status' => 'processing',
                'started_at' => now(),
                'completed_at' => null,
                'error_message' => null,
                'total_rows' => $totalCsvRows,
                'processed_rows' => 0,
                'matched_rows' => 0,
                'pending_rows' => 0,
                'skipped_rows' => 0,
                'failed_rows' => 0,
                'invalid_file_path' => null,
            ]);

            $validChunk = [];
            $invalidChunk = [];
            $chunkSize = 500;

            // Read and classify each CSV row without loading the full file.
            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                // Read the original contact from the CSV.
                $originalContact = $this->getCsvValue(
                    $row,
                    $headerIndexes,
                    'Contact'
                );

                // Normalize the contact before validating it.
                $normalizedContact = $this->normalizeMobileNumber(
                    $originalContact
                );

                // Preserve the complete original row when the mobile is invalid.
                if (!$this->isValidMobileNumber($normalizedContact)) {
                    $invalidChunk[] = $row;
                } else {
                    // Prepare only valid normalized mobile contacts for database import.
                    $validChunk[] = [
                        'course_name' => $this->getCsvValue(
                            $row,
                            $headerIndexes,
                            'Course Name'
                        ),

                        // Save only the normalized 10-digit mobile number.
                        'contact' => $normalizedContact,

                        'first_name' => $this->getCsvValue(
                            $row,
                            $headerIndexes,
                            'First Name'
                        ),

                        'last_name' => $this->getCsvValue(
                            $row,
                            $headerIndexes,
                            'Last Name'
                        ),

                        'my_groups' => $this->getCsvValue(
                            $row,
                            $headerIndexes,
                            'My groups'
                        ),

                        'age_in_years' => $this->getCsvValue(
                            $row,
                            $headerIndexes,
                            'Age (In Years)'
                        ),

                        'gender' => $this->getCsvValue(
                            $row,
                            $headerIndexes,
                            'Gender'
                        ),

                        'state' => $this->getCsvValue(
                            $row,
                            $headerIndexes,
                            'State'
                        ),

                        'data_sharing_consent' => $this->getCsvValue(
                            $row,
                            $headerIndexes,
                            $consentHeaderAliases
                        ),

                        'course_completion' => $this->getCsvValue(
                            $row,
                            $headerIndexes,
                            'Course Completion'
                        ),
                    ];
                }

                // Process each chunk when the combined row count reaches the limit.
                if (
                    count($validChunk) + count($invalidChunk) >= $chunkSize
                ) {
                    if (!empty($validChunk)) {
                        $this->processChunk($validChunk, $batch->batch_id);
                        $validChunk = [];
                    }

                    if (!empty($invalidChunk)) {
                        $invalidFilePath = $this->appendInvalidContacts(
                            $invalidChunk,
                            $headers,
                            $invalidFilePath,
                            $invalidHandle
                        );

                        $batch->increment(
                            'skipped_rows',
                            count($invalidChunk)
                        );

                        $invalidChunk = [];
                    }
                }
            }

            // Process the remaining valid records.
            if (!empty($validChunk)) {
                $this->processChunk($validChunk, $batch->batch_id);
            }

            // Export the remaining invalid contacts.
            if (!empty($invalidChunk)) {
                $invalidFilePath = $this->appendInvalidContacts(
                    $invalidChunk,
                    $headers,
                    $invalidFilePath,
                    $invalidHandle
                );

                $batch->increment(
                    'skipped_rows',
                    count($invalidChunk)
                );
            }

            // Close the invalid CSV output file after all rows are written.
            if (is_resource($invalidHandle)) {
                fclose($invalidHandle);
                $invalidHandle = null;
            }

            // Verify imported and skipped rows account for the complete input.
            $finalBatch = LearnerCourseImportBatch::where(
                'batch_id',
                $batch->batch_id
            )->firstOrFail();

            $completedInputRows =
                (int) $finalBatch->processed_rows
                + (int) $finalBatch->skipped_rows;

            if ($completedInputRows !== $totalCsvRows) {
                throw new \RuntimeException(
                    "Import count mismatch. Expected {$totalCsvRows} input rows, "
                    . "but {$completedInputRows} rows were processed or skipped."
                );
            }

            // Save the invalid CSV path and mark the batch completed.
            $batch->update([
                'status' => 'completed',
                'invalid_file_path' => $invalidFilePath,
                'completed_at' => now(),
                'error_message' => null,
            ]);

        } catch (Throwable $exception) {
            // Close the invalid output file if processing fails.
            if (is_resource($invalidHandle)) {
                fclose($invalidHandle);
            }

            // Preserve the generated invalid CSV path when available.
            $batch->update([
                'status' => 'failed',
                'invalid_file_path' => $invalidFilePath,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            throw $exception;

        } finally {
            // Always close the original CSV input file.
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    /**
     * Normalize an Indian mobile number by removing country code and formatting characters.
     */
    private function normalizeMobileNumber(?string $contact): ?string
    {
        // Return null for missing or empty contacts.
        if ($contact === null || trim($contact) === '') {
            return null;
        }

        // Keep digits only, removing spaces, plus signs, hyphens, and brackets.
        $digits = preg_replace('/\D+/', '', trim($contact));

        if ($digits === null || $digits === '') {
            return null;
        }

        // Remove the Indian country code when followed by a 10-digit number.
        if (
            strlen($digits) === 12
            && str_starts_with($digits, '91')
        ) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Validate a normalized 10-digit Indian mobile number.
     */
    private function isValidMobileNumber(?string $contact): bool
    {
        return preg_match('/^[6-9]\d{9}$/', (string) $contact) === 1;
    }

    /**
     * Append invalid contact rows to the batch-specific CSV export file.
     */
    private function appendInvalidContacts(
        array $rows,
        array $headers,
        ?string $invalidFilePath,
        mixed &$invalidHandle
    ): string {
        // Create the invalid contacts file only when the first invalid row appears.
        if ($invalidFilePath === null) {
            $invalidFilePath = 'learner-course-imports/'
                . $this->batchId
                . '-invalid-contacts.csv';

            Storage::disk('local')->makeDirectory(
                'learner-course-imports'
            );

            $absolutePath = Storage::disk('local')->path(
                $invalidFilePath
            );

            $invalidHandle = fopen($absolutePath, 'w');

            if ($invalidHandle === false) {
                throw new \RuntimeException(
                    'Unable to create the invalid contacts CSV file.'
                );
            }

            // Add a UTF-8 BOM so Excel can recognize UTF-8 CSV content.
            fwrite($invalidHandle, "\xEF\xBB\xBF");

            // Preserve the original CSV headers.
            fputcsv($invalidHandle, $headers, ',', '"', '');
        }

        if (!is_resource($invalidHandle)) {
            throw new \RuntimeException(
                'Invalid contacts CSV file is not available for writing.'
            );
        }

        // Write every invalid row with its original column values.
        foreach ($rows as $row) {
            if (fputcsv($invalidHandle, $row, ',', '"', '') === false) {
                throw new \RuntimeException(
                    'Unable to write an invalid contact row to the CSV file.'
                );
            }
        }

        // Flush written rows to disk before returning the file path.
        fflush($invalidHandle);

        return $invalidFilePath;
    }

    /**
     * Count all non-empty CSV data rows, excluding the header row.
     */
    private function countCsvRows(string $filePath): int
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to count CSV records.');
        }

        try {
            // Skip the CSV header row.
            fgetcsv($handle, 0, ',', '"', '');

            $totalRows = 0;

            // Count every non-empty data row.
            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                $totalRows++;
            }

            return $totalRows;

        } finally {
            // Close the counting file handle.
            fclose($handle);
        }
    }

    /**
     * Normalize CSV headers by removing BOM, invisible characters, and inconsistent whitespace.
     */
    private function normalizeHeader(string $header): string
    {
        // Remove UTF-8 BOM from the beginning of the header.
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

        // Remove zero-width Unicode formatting characters.
        $header = str_replace(
            ["\xE2\x80\x8B", "\xE2\x80\x8C", "\xE2\x80\x8D", "\xEF\xBB\xBF"],
            '',
            $header
        );

        // Convert non-breaking spaces and repeated whitespace into normal spaces.
        $normalized = preg_replace('/[\s\p{Z}]+/u', ' ', $header);

        // Fall back to basic whitespace normalization if the header has invalid UTF-8.
        if ($normalized === null) {
            $normalized = preg_replace('/\s+/', ' ', $header) ?? $header;
        }

        return strtolower(trim($normalized));
    }

    /**
     * Read a CSV cell safely using one header name or a list of header aliases.
     */
    private function getCsvValue(
        array $row,
        array $headerIndexes,
        string|array $headers
    ): ?string {
        // Convert a single header name into an alias list.
        $headers = is_array($headers) ? $headers : [$headers];

        // Find the first matching header alias.
        foreach ($headers as $header) {
            $normalizedHeader = $this->normalizeHeader($header);

            $index = $headerIndexes[$normalizedHeader] ?? null;

            if ($index === null || !array_key_exists($index, $row)) {
                continue;
            }

            $value = trim((string) $row[$index]);

            return $value === '' ? null : $value;
        }

        return null;
    }

    /**
     * Match learners and insert imported CSV records in one database transaction.
     */
    private function processChunk(array $rows, string $batchId): void
    {
        // Collect unique non-empty normalized contacts for learner matching.
        $contacts = collect($rows)
            ->pluck('contact')
            ->filter()
            ->unique()
            ->values();

        // Find existing learners using their registered phone numbers.
        $learners = $contacts->isEmpty()
            ? collect()
            : Learner::whereIn('phone_number', $contacts)
                ->get(['id', 'phone_number'])
                ->keyBy('phone_number');

        $now = now();
        $records = [];
        $matchedCount = 0;
        $pendingCount = 0;
        $completedLearnerIds = [];

        foreach ($rows as $row) {
            // Match the normalized CSV contact against an existing learner.
            $learner = $row['contact']
                ? $learners->get($row['contact'])
                : null;

            $isMatched = $learner !== null;

            // Convert numeric course completion values into integers.
            $completion = is_numeric($row['course_completion'])
                ? (int) $row['course_completion']
                : null;

            if ($isMatched) {
                $matchedCount++;

                // Mark matched learners completed only when completion is exactly 100.
                if ($completion === 100) {
                    $completedLearnerIds[] = $learner->id;
                }
            } else {
                $pendingCount++;
            }

            // Prepare every valid-mobile CSV row, including unmatched contacts.
            $records[] = [
                'import_batch_id' => $batchId,
                'course_name' => $row['course_name'],
                'contact' => $row['contact'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'my_groups' => $row['my_groups'],
                'age_in_years' => $row['age_in_years'],
                'gender' => $row['gender'],
                'state' => $row['state'],
                'data_sharing_consent' => $row['data_sharing_consent'],
                'course_completion' => $completion,
                'learner_id' => $learner?->id,
                'match_status' => $isMatched ? 'matched' : 'pending',
                'matched_at' => $isMatched ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Save imported rows, learner completion updates, and progress atomically.
        DB::transaction(function () use (
            $records,
            $completedLearnerIds,
            $batchId,
            $matchedCount,
            $pendingCount
        ): void {
            // Store all imported CSV records.
            DB::table('learner_course_imports')->insert($records);

            // Mark learners completed when their imported completion is exactly 100.
            if (!empty($completedLearnerIds)) {
                Learner::whereIn('id', array_unique($completedLearnerIds))
                    ->update(['course_completed' => 1]);
            }

            // Increase processed rows only after the insert succeeds.
            $chunkCount = count($records);

            LearnerCourseImportBatch::where('batch_id', $batchId)
                ->increment('processed_rows', $chunkCount);

            // Update matched and pending row counters.
            LearnerCourseImportBatch::where('batch_id', $batchId)
                ->increment('matched_rows', $matchedCount);

            LearnerCourseImportBatch::where('batch_id', $batchId)
                ->increment('pending_rows', $pendingCount);
        });
    }

    /**
     * Mark the batch as failed when Laravel permanently fails the queued job.
     */
    public function failed(?Throwable $exception): void
    {
        LearnerCourseImportBatch::where('batch_id', $this->batchId)
            ->update([
                'status' => 'failed',
                'error_message' => $exception?->getMessage(),
                'completed_at' => now(),
            ]);
    }
}
