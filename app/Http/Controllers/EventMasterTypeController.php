<?php

namespace App\Http\Controllers;

use App\Models\EventMasterType;
use Illuminate\Http\Request;
use App\Models\EventCategoryMaster;
use App\Models\EventMaster;
use Illuminate\Http\RedirectResponse;

class EventMasterTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = EventMasterType::query();

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $eventTypes = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $filters = [
            'search' => $request->search,
            'status' => $request->status,
        ];

        return view(
            'admin.event-types.index',
            compact(
                'eventTypes',
                'filters'
            )
        );
    }

    public function create()
    {
        return view('admin.event-types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
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

        EventMasterType::create($validated);

        return redirect()
            ->route('admin.event-types.index')
            ->with(
                'status',
                'Event type created successfully.'
            );
    }

    public function edit(EventMasterType $eventType)
    {
        return view(
            'admin.event-types.edit',
            compact('eventType')
        );
    }

    public function update(
        Request $request,
        EventMasterType $eventType
    ): RedirectResponse {
        $validated = $request->validate([
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

        $eventType->update($validated);

        return redirect()
            ->route('admin.event-types.index')
            ->with(
                'status',
                'Event type updated successfully.'
            );
    }

    public function destroy(
        EventMasterType $eventType
    ): RedirectResponse {
        if ($eventType->categories()->exists()) {
            return redirect()
                ->route('admin.event-types.index')
                ->with(
                    'status',
                    'This event type cannot be deleted because it is being used by an event category.'
                );
        }

        if ($eventType->eventMasters()->exists()) {
            return redirect()
                ->route('admin.event-types.index')
                ->with(
                    'status',
                    'This event type cannot be deleted because it is being used by an event master.'
                );
        }

        $eventType->delete();

        return redirect()
            ->route('admin.event-types.index')
            ->with(
                'status',
                'Event type deleted successfully.'
            );
    }
}