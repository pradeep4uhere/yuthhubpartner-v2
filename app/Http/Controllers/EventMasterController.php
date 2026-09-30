<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EventMaster;
use App\Models\EventMasterType;
use App\Models\EventCategoryMaster;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;


class EventMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = EventMaster::with([
            'eventMasterType',
            'eventCategory',
        ]);

        /*
         * Search
         */
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('event_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('eligibility', 'like', "%{$search}%");
            });
        }

        /*
         * Filter by Event Type
         */
        if ($request->filled('event_master_type_id')) {
            $query->where(
                'event_master_type_id',
                $request->event_master_type_id
            );
        }

        /*
         * Filter by Category
         */
        if ($request->filled('event_category_id')) {
            $query->where(
                'event_category_id',
                $request->event_category_id
            );
        }

        /*
         * Filter by Status
         */
        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        $eventMasters = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        /*
         * Dropdown data
         */
        $eventMasterTypes = EventMasterType::where('status', 1)
            ->orderBy('name')
            ->get();

        $eventCategories = EventCategoryMaster::where('status', 1)
            ->orderBy('name')
            ->get();

        $filters = [
            'search' => $request->search,
            'event_master_type_id' => $request->event_master_type_id,
            'event_category_id' => $request->event_category_id,
            'status' => $request->status,
        ];

        return view('admin.event-masters.index', compact(
            'eventMasters',
            'eventMasterTypes',
            'eventCategories',
            'filters'
        ));
    }



     /**
     * Show create form.
     */
    public function create()
    {
        $eventMasterTypes = EventMasterType::where('status', 1)
            ->orderBy('name')
            ->get();

        $eventCategories = collect();

        return view(
            'admin.event-masters.create',
            compact(
                'eventMasterTypes',
                'eventCategories'
            )
        );
    }

    /**
     * Store new event master.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_master_type_id' => [
                'required',
                'integer',
                'exists:event_master_type,id',
            ],

            'event_category_id' => [
                'required',
                'integer',
                'exists:event_category_master,id',
            ],

            'event_name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'eligibility' => [
                'nullable',
                'string',
                'max:255',
            ],

            'fees_per_transactions' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'document_name_1' => [
                'nullable',
                'string',
                'max:255',
            ],

            'document_name_2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'document_name_3' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                'boolean',
            ],
        ]);

        EventMaster::create($validated);

        return redirect()
            ->route('admin.event-masters.index')
            ->with('status', 'Event master created successfully.');
    }

    /**
     * Show edit form.
     */
   public function edit(EventMaster $eventMaster)
{
    $eventMasterTypes = EventMasterType::where('status', 1)
        ->orderBy('name')
        ->get();

    $eventCategories = EventCategoryMaster::where('status', 1)
        ->where(
            'event_master_type_id',
            $eventMaster->event_master_type_id
        )
        ->orderBy('name')
        ->get();

    return view(
        'admin.event-masters.edit',
        compact(
            'eventMaster',
            'eventMasterTypes',
            'eventCategories'
        )
    );
}

    /**
     * Update event master.
     */
    public function update(
        Request $request,
        EventMaster $eventMaster
    ): RedirectResponse {

        $validated = $request->validate([
            'event_master_type_id' => [
                'required',
                'integer',
                'exists:event_master_type,id',
            ],

            'event_category_id' => [
                'required',
                'integer',
                'exists:event_category_master,id',
            ],

            'event_name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'eligibility' => [
                'nullable',
                'string',
                'max:255',
            ],

            'fees_per_transactions' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'document_name_1' => [
                'nullable',
                'string',
                'max:255',
            ],

            'document_name_2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'document_name_3' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                'boolean',
            ],
        ]);

        $eventMaster->update($validated);

        return redirect()
            ->route('admin.event-masters.index')
            ->with('status', 'Event master updated successfully.');
    }

    /**
     * Delete event master.
     */
   public function destroy(
        EventMaster $eventMaster
    ): RedirectResponse {
        $eventMaster->delete();

        return redirect()
            ->route('admin.event-masters.index')
            ->with(
                'status',
                'Event master deleted successfully.'
            );
    }




    /**
     *
     * Fetch Category Based On  Event
    */
    public function categories(EventMasterType $eventMasterType)
    {
        $categories = EventCategoryMaster::where(
            'event_master_type_id',
            $eventMasterType->id
        )
            ->where('status', 1)
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        return response()->json($categories);
    }
}
