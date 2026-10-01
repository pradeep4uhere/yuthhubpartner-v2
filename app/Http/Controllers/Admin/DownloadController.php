<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Export;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function index(Request $request)
    {
        $admin = auth('admin')->user();

        $query = Export::query()
            ->where('requested_by_type', get_class($admin))
            ->where('requested_by_id', $admin->id);

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('scope_type')) {
            $query->where('scope_type', $request->scope_type);
        }

        /*
        |--------------------------------------------------------------------------
        | Analytics
        |--------------------------------------------------------------------------
        */

        $totalExports = (clone $query)->count();

        $completedExports = (clone $query)
            ->where('status', 'completed')
            ->count();

        $processingExports = (clone $query)
            ->whereIn('status', ['queued', 'processing'])
            ->count();

        $failedExports = (clone $query)
            ->where('status', 'failed')
            ->count();

        $todayExports = (clone $query)
            ->whereDate('created_at', today())
            ->count();

        $weeklyExports = (clone $query)
            ->where(
                'created_at',
                '>=',
                now()->subDays(6)->startOfDay()
            )
            ->count();

        $totalExportedRecords = (clone $query)
            ->where('status', 'completed')
            ->sum('total_records');

        $successRate = $totalExports > 0
            ? round(($completedExports / $totalExports) * 100, 1)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 7 Day Export Trend
        |--------------------------------------------------------------------------
        */

        $exportTrend = collect(range(6, 0))
            ->map(function ($daysAgo) use ($query) {
                $date = now()->subDays($daysAgo);

                return [
                    'date' => $date->format('Y-m-d'),
                    'label' => $date->format('D'),
                    'count' => (clone $query)
                        ->whereDate('created_at', $date->toDateString())
                        ->count(),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Export Type Statistics
        |--------------------------------------------------------------------------
        */

        $exportTypeStats = (clone $query)
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Downloads List
        |--------------------------------------------------------------------------
        */

        $exports = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Filter Options
        |--------------------------------------------------------------------------
        */

        $exportTypes = Export::query()
            ->where('requested_by_type', get_class($admin))
            ->where('requested_by_id', $admin->id)
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        return view('admin.downloads.index', compact(
            'exports',
            'totalExports',
            'completedExports',
            'processingExports',
            'failedExports',
            'todayExports',
            'weeklyExports',
            'totalExportedRecords',
            'successRate',
            'exportTrend',
            'exportTypeStats',
            'exportTypes'
        ));
    }

    public function status(LearnerExport $export): JsonResponse
    {
        $admin = auth('admin')->user();

        abort_unless(
            $admin &&
            (int) $export->admin_id === (int) $admin->id,
            403
        );

        return response()->json([
            'id' => $export->id,
            'status' => $export->status,
            'total_records' => $export->total_records,
            'file_name' => $export->file_name,
            'started_at' => optional($export->started_at)
                ->format('d M Y, h:i A'),
            'completed_at' => optional($export->completed_at)
                ->format('d M Y, h:i A'),
            'download_url' => $export->status === 'completed'
                ? route('admin.downloads.download', $export)
                : null,
            'error_message' => $export->error_message,
        ]);
    }



    public function sdownload(Export $export)
{
    dd([
        'id' => $export->id,
        'status' => $export->status,
        'file_path' => $export->file_path,
        'file_name' => $export->file_name,
        'exists' => Storage::disk('local')->exists($export->file_path),
    ]);
}



public function download(Export $export)
{
    $admin = auth('admin')->user();

    abort_unless(
        $export->requested_by_type === get_class($admin)
        && $export->requested_by_id === $admin->id,
        403
    );

    abort_unless(
        $export->status === 'completed',
        404
    );

    abort_unless(
        $export->file_path &&
        Storage::disk('local')->exists($export->file_path),
        404
    );

    return Storage::disk('local')->download(
        $export->file_path,
        $export->file_name
    );
}


    public function ssdownload(LearnerExport $export)
    {
        $admin = auth('admin')->user();

        abort_unless(
            $admin &&
            (int) $export->admin_id === (int) $admin->id,
            403
        );

        abort_unless(
            $export->status === 'completed',
            404
        );

        abort_unless(
            $export->file_path &&
            \Storage::disk('local')->exists($export->file_path),
            404
        );

        return \Storage::disk('local')->download(
            $export->file_path,
            $export->file_name
        );
    }
}
