<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Export;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PartnerDownloadController extends Controller
{
    private function partnerExport($id): Export
    {
        $partner = auth('partner')->user();

        return Export::query()
            ->where('requested_by_type', get_class($partner))
            ->where('requested_by_id', $partner->id)
            ->findOrFail($id);
    }

    public function index(Request $request)
    {
        $partner = auth('partner')->user();

        $query = Export::query()
            ->where('requested_by_type', get_class($partner))
            ->where('requested_by_id', $partner->id);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') &&
            in_array($request->status, [
                'queued', 'processing', 'completed', 'failed'
            ], true)) {
            $query->where('status', $request->status);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'completed' => (clone $query)
                ->where('status', 'completed')->count(),
            'processing' => (clone $query)
                ->whereIn('status', ['queued', 'processing'])->count(),
            'failed' => (clone $query)
                ->where('status', 'failed')->count(),
        ];

        $exports = $query
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'exports' => $exports,
        ]);
    }

    public function status($id)
    {
        $export = $this->partnerExport($id);

        return response()->json([
            'success' => true,
            'export' => $export->only([
                'id',
                'status',
                'total_records',
                'error_message',
            ]),
        ]);
    }

    public function download($id)
    {
        $export = $this->partnerExport($id);

        abort_unless($export->status === 'completed', 409,
            'Export is not completed yet.');

        abort_unless($export->file_path, 404);

        // IMPORTANT: Use the same disk configured in your exporter.
        $disk = Storage::disk(config('filesystems.default'));

        abort_unless($disk->exists($export->file_path), 404,
            'Export file not found.');

        return $disk->download(
            $export->file_path,
            $export->file_name ?: basename($export->file_path)
        );
    }
}
