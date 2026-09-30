<?php

namespace App\Http\Controllers;

use App\Models\EventCategoryMaster;
use App\Models\EventMasterType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class EventCategoryMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = EventCategoryMaster::with('eventMasterType');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where('name', 'like', "%{$search}%");
        }

        /*
        |--------------------------------------------------------------------------
        | Event Type Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('event_master_type_id')) {
            $query->where(
                'event_master_type_id',
                $request->event_master_type_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        $eventCategories = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $eventMasterTypes = EventMasterType::orderBy('name')
            ->get();

        $filters = [
            'search' => $request->search,
            'event_master_type_id' => $request->event_master_type_id,
            'status' => $request->status,
        ];

        return view(
            'admin.event-categories.index',
            compact(
                'eventCategories',
                'eventMasterTypes',
                'filters'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $eventMasterTypes = EventMasterType::where('status', 1)
            ->orderBy('name')
            ->get();

        return view(
            'admin.event-categories.create',
            compact('eventMasterTypes')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_master_type_id' => [
                'required',
                'integer',
                'exists:event_master_type,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                'boolean',
            ],
        ]);

        EventCategoryMaster::create($validated);

        return redirect()
            ->route('admin.event-categories.index')
            ->with(
                'status',
                'Event category created successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */
    public function edit(EventCategoryMaster $eventCategory)
    {
        $eventMasterTypes = EventMasterType::where('status', 1)
            ->orderBy('name')
            ->get();

        return view(
            'admin.event-categories.edit',
            compact(
                'eventCategory',
                'eventMasterTypes'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        EventCategoryMaster $eventCategory
    ): RedirectResponse {

        $validated = $request->validate([
            'event_master_type_id' => [
                'required',
                'integer',
                'exists:event_master_type,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                'boolean',
            ],
        ]);

        $eventCategory->update($validated);

        return redirect()
            ->route('admin.event-categories.index')
            ->with(
                'status',
                'Event category updated successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */
    public function destroy(
        EventCategoryMaster $eventCategory
    ): RedirectResponse {

        /*
        |--------------------------------------------------------------------------
        | Prevent deletion if category is being used by Event Master
        |--------------------------------------------------------------------------
        */
        if ($eventCategory->eventMasters()->exists()) {
            return redirect()
                ->route('admin.event-categories.index')
                ->with(
                    'status',
                    'This category cannot be deleted because it is being used by an event master.'
                );
        }

        $eventCategory->delete();

        return redirect()
            ->route('admin.event-categories.index')
            ->with(
                'status',
                'Event category deleted successfully.'
            );
    }
}
