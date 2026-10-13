<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LogHistoryController extends Controller
{
    /**
     * Keep this bounded: log files and database history can grow very quickly.
     * This page merges recent entries from supported sources, then filters/paginates them.
     */
    private const MAX_FILE_ENTRIES = 2500;
    private const MAX_DB_ROWS_PER_SOURCE = 500;
    private const PAGE_SIZE = 50;

    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'level' => strtoupper(trim((string) $request->query('level', ''))),
            'source' => trim((string) $request->query('source', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $entries = collect()
            ->concat($this->readLaravelFileLogs())
            ->concat($this->readLearnerImportHistory())
            ->concat($this->readExportHistory())
            ->concat($this->readQueueHistory());

        $totalBeforeFilter = $entries->count();

        $filtered = $entries->filter(function (array $entry) use ($filters) {
            if ($filters['level'] !== '' && strtoupper($entry['level']) !== $filters['level']) {
                return false;
            }

            if ($filters['source'] !== '' && $entry['source_key'] !== $filters['source']) {
                return false;
            }

            if ($filters['search'] !== '') {
                $haystack = Str::lower(
                    ($entry['title'] ?? '') . ' ' .
                    ($entry['message'] ?? '') . ' ' .
                    ($entry['source'] ?? '') . ' ' .
                    ($entry['context'] ?? '')
                );

                if (!Str::contains($haystack, Str::lower($filters['search']))) {
                    return false;
                }
            }

            $createdAt = $entry['created_at'] ?? null;
            if ($filters['date_from'] !== '' && (!$createdAt || substr($createdAt, 0, 10) < $filters['date_from'])) {
                return false;
            }
            if ($filters['date_to'] !== '' && (!$createdAt || substr($createdAt, 0, 10) > $filters['date_to'])) {
                return false;
            }

            return true;
        })->sortByDesc('timestamp')->values();

        $page = max(1, LengthAwarePaginator::resolveCurrentPage());
        $pageItems = $filtered->slice(($page - 1) * self::PAGE_SIZE, self::PAGE_SIZE)->values();

        $logs = new LengthAwarePaginator(
            $pageItems,
            $filtered->count(),
            self::PAGE_SIZE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $summary = [
            'total' => $totalBeforeFilter,
            'errors' => $entries->whereIn('level', ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'])->count(),
            'warnings' => $entries->where('level', 'WARNING')->count(),
            'today' => $entries->filter(fn ($e) => !empty($e['created_at']) && substr($e['created_at'], 0, 10) === now()->toDateString())->count(),
        ];

        $sources = [
            'laravel' => 'Laravel Files',
            'learner_import' => 'Learner Imports',
            'export' => 'Exports',
            'queue' => 'Queue Jobs',
        ];
        //dd($logs, $filters, $summary, $sources);

        return view('admin.log-history.index', compact('logs', 'filters', 'summary', 'sources'));
    }

    private function readLaravelFileLogs()
    {
        $directory = storage_path('logs');
        if (!is_dir($directory)) {
            return collect();
        }

        $files = collect(glob($directory . DIRECTORY_SEPARATOR . '*.log') ?: [])
            ->sortByDesc(fn ($file) => @filemtime($file) ?: 0)
            ->take(10);

        $entries = collect();

        foreach ($files as $file) {
            if (!is_readable($file)) {
                continue;
            }

            // Read only the end of each file so a very large log cannot exhaust memory.
            $handle = @fopen($file, 'rb');
            if (!$handle) {
                continue;
            }

            $size = filesize($file) ?: 0;
            $readBytes = min($size, 2 * 1024 * 1024);
            if ($size > $readBytes) {
                fseek($handle, -$readBytes, SEEK_END);
                fgets($handle); // discard partial first line
            }

            $chunk = stream_get_contents($handle);
            fclose($handle);

            if (!is_string($chunk) || $chunk === '') {
                continue;
            }

            // Laravel log entries normally start with: [2026-10-12 12:00:00] production.ERROR:
            $parts = preg_split('/(?=^\[[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\])/m', trim($chunk), -1, PREG_SPLIT_NO_EMPTY);
            foreach (array_slice($parts ?: [], -self::MAX_FILE_ENTRIES) as $part) {
                if (!preg_match('/^\[([^\]]+)\]\s+([^.:\s]+)\.([A-Z]+):\s*(.*)$/s', trim($part), $match)) {
                    continue;
                }

                $date = $match[1];
                $level = strtoupper($match[3]);
                $message = trim($match[4]);
                try {
                    $timestamp = strtotime($date) ?: filemtime($file) ?: time();
                } catch (Throwable $e) {
                    $timestamp = filemtime($file) ?: time();
                }

                $entries->push([
                    'id' => sha1($file . '|' . $date . '|' . $message),
                    'source_key' => 'laravel',
                    'source' => basename($file),
                    'level' => $this->normalizeLevel($level),
                    'title' => Str::limit(Str::before($message, "\n"), 140),
                    'message' => Str::limit($message, 1200),
                    'context' => '',
                    'details' => $part,
                    'created_at' => date('Y-m-d H:i:s', $timestamp),
                    'timestamp' => $timestamp,
                ]);
            }
        }

        return $entries->take(self::MAX_FILE_ENTRIES);
    }

    private function readLearnerImportHistory()
    {
        $table = 'learner_imports';
        if (!Schema::hasTable($table)) {
            return collect();
        }

        $columns = Schema::getColumnListing($table);
        $rows = DB::table($table)->orderByDesc(in_array('created_at', $columns, true) ? 'created_at' : 'id')
            ->limit(self::MAX_DB_ROWS_PER_SOURCE)->get();

        return $rows->map(function ($row) use ($columns) {
            $data = (array) $row;
            $status = strtolower((string) ($data['status'] ?? 'info'));
            $level = in_array($status, ['failed', 'error'], true) ? 'ERROR'
                : (in_array($status, ['processing', 'queued', 'pending'], true) ? 'INFO' : 'INFO');

            $message = $this->firstValue($data, ['error_message', 'message', 'remarks', 'status_message']);
            $title = 'Learner import #' . ($data['id'] ?? '');
            if ($message === '') {
                $message = 'Import status: ' . ($data['status'] ?? 'record found');
            }

            $created = $this->firstValue($data, ['created_at', 'started_at', 'updated_at']);
            $timestamp = $created !== '' ? (strtotime($created) ?: time()) : time();

            return [
                'id' => 'import-' . ($data['id'] ?? sha1(json_encode($data))),
                'source_key' => 'learner_import',
                'source' => 'learner_imports',
                'level' => $level,
                'title' => $title,
                'message' => Str::limit($message, 1200),
                'context' => $this->safeContext($data, ['id', 'status', 'program_code', 'file_name', 'total_rows', 'success_count', 'duplicate_count', 'failed_count', 'uploaded_by']),
                'details' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'created_at' => $created,
                'timestamp' => $timestamp,
            ];
        });
    }

    private function readExportHistory()
    {
        // Update/add the table name here if your existing downloads module uses a different table.
        $table = collect(['admin_exports', 'exports', 'export_histories', 'export_logs'])
            ->first(fn ($name) => Schema::hasTable($name));

        if (!$table) {
            return collect();
        }

        $columns = Schema::getColumnListing($table);
        $orderColumn = in_array('created_at', $columns, true) ? 'created_at' : 'id';

        return DB::table($table)->orderByDesc($orderColumn)->limit(self::MAX_DB_ROWS_PER_SOURCE)->get()
            ->map(function ($row) use ($table) {
                $data = (array) $row;
                $status = strtolower((string) ($data['status'] ?? 'info'));
                $level = in_array($status, ['failed', 'error'], true) ? 'ERROR' : 'INFO';
                $message = $this->firstValue($data, ['error_message', 'message', 'title', 'file_name']);
                $created = $this->firstValue($data, ['created_at', 'updated_at']);
                $timestamp = $created !== '' ? (strtotime($created) ?: time()) : time();

                return [
                    'id' => 'export-' . ($data['id'] ?? sha1(json_encode($data))),
                    'source_key' => 'export',
                    'source' => $table,
                    'level' => $level,
                    'title' => 'Export #' . ($data['id'] ?? '') . ' — ' . ($data['title'] ?? $data['type'] ?? 'Export activity'),
                    'message' => $message !== '' ? Str::limit($message, 1200) : 'Export status: ' . ($data['status'] ?? 'record found'),
                    'context' => $this->safeContext($data, ['id', 'type', 'status', 'total_records', 'scope_type', 'scope_id', 'file_name']),
                    'details' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                    'created_at' => $created,
                    'timestamp' => $timestamp,
                ];
            });
    }

    private function readQueueHistory()
    {
        $entries = collect();

        if (Schema::hasTable('failed_jobs')) {
            $rows = DB::table('failed_jobs')->orderByDesc('failed_at')->limit(self::MAX_DB_ROWS_PER_SOURCE)->get();
            foreach ($rows as $row) {
                $data = (array) $row;
                $created = $data['failed_at'] ?? $data['created_at'] ?? null;
                $timestamp = $created ? (strtotime($created) ?: time()) : time();

                $entries->push([
                    'id' => 'failed-job-' . ($data['id'] ?? sha1(json_encode($data))),
                    'source_key' => 'queue',
                    'source' => 'failed_jobs',
                    'level' => 'ERROR',
                    'title' => 'Failed queue job #' . ($data['id'] ?? ''),
                    'message' => Str::limit((string) ($data['exception'] ?? 'Queue job failed'), 1200),
                    'context' => $this->safeContext($data, ['id', 'uuid', 'queue', 'connection', 'failed_at']),
                    'details' => (string) ($data['exception'] ?? json_encode($data, JSON_PRETTY_PRINT)),
                    'created_at' => $created,
                    'timestamp' => $timestamp,
                ]);
            }
        }

        return $entries;
    }

    private function normalizeLevel(string $level): string
    {
        return in_array($level, ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'], true)
            ? $level : 'INFO';
    }

    private function firstValue(array $data, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_scalar($data[$key]) && trim((string) $data[$key]) !== '') {
                return trim((string) $data[$key]);
            }
        }

        return '';
    }

    private function safeContext(array $data, array $keys): string
    {
        $safe = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && is_scalar($data[$key])) {
                $safe[$key] = $data[$key];
            }
        }

        return json_encode($safe, JSON_UNESCAPED_SLASHES) ?: '';
    }




    public function clear()
    {
        $directory = storage_path('logs');

        if (!File::isDirectory($directory)) {
            return redirect()
                ->route('admin.logs.index')
                ->with('status', 'Log directory was not found.');
        }

        $cleared = 0;

        foreach (File::files($directory) as $file) {
            if (strtolower($file->getExtension()) !== 'log') {
                continue;
            }

            File::put($file->getPathname(), '');
            $cleared++;
        }

        Log::info('Laravel application log files cleared by admin.', [
            'admin_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.logs.index')
            ->with('status', "Successfully cleared {$cleared} application log files.");
    }





}
