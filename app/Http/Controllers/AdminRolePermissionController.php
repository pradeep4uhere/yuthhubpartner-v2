<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AdminRolePermissionController extends Controller
{
    private const PERMISSION_GROUPS = [
        'Learners' => [
            'learners.view' => 'View learners',
            'learners.update' => 'Update learner records',
        ],
        'Learning' => [
            'learning.manage' => 'Manage learning programmes',
        ],
        'Events' => [
            'events.manage' => 'Manage events',
        ],
        'Placements' => [
            'placements.manage' => 'Manage placements',
        ],
        'Verification' => [
            'verification.manage' => 'Manage verification',
        ],
        'Reports' => [
            'reports.export' => 'Export reports',
        ],
        'Partners' => [
            'partners.view' => 'View partners',
            'partners.create' => 'Create partners',
            'partners.update' => 'Edit partners',
            'partners.delete' => 'Delete partners',
        ],
        'Partner centers' => [
            'partner_centers.view' => 'View partner centers',
            'partner_centers.create' => 'Create partner centers',
            'partner_centers.update' => 'Edit partner centers',
            'partner_centers.delete' => 'Delete partner centers',
        ],
        'Placement users' => [
            'partner_placement_users.view' => 'View placement users',
            'partner_placement_users.create' => 'Create placement users',
            'partner_placement_users.update' => 'Edit placement users',
            'partner_placement_users.delete' => 'Delete placement users',
        ],


        'Field agents' => [
            'field_agents.view' => 'View field agents',
            'field_agents.create' => 'Create field agents',
            'field_agents.update' => 'Edit field agents',
            'field_agents.delete' => 'Delete field agents',
        ],

        'Event Types' => [
            'event_types.view' => 'View event types',
            'event_types.create' => 'Create event types',
            'event_types.update' => 'Edit event types',
            'event_types.delete' => 'Delete event types',
        ],

        'Event Categories' => [
            'event_categories.view' => 'View event categories',
            'event_categories.create' => 'Create event categories',
            'event_categories.update' => 'Edit event categories',
            'event_categories.delete' => 'Delete event categories',
        ],

        'Event Masters' => [
            'event_masters.view' => 'View event masters',
            'event_masters.create' => 'Create event masters',
            'event_masters.update' => 'Edit event masters',
            'event_masters.delete' => 'Delete event masters',
        ],
    ];

    public function edit(): View
    {
        $role = Role::findByName('Sub Admin', 'admin');

        return view('admin.users.permissions', [
            'groups' => self::PERMISSION_GROUPS,
            'assignedPermissions' => $role->permissions()->pluck('name')->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in($this->editablePermissionNames())],
        ]);

        $role = Role::findByName('Sub Admin', 'admin');
        $role->syncPermissions(array_merge(
            ['admin.access'],
            $validated['permissions'] ?? [],
        ));

        return redirect()
            ->route('admin.users.permissions.edit')
            ->with('status', 'Sub Admin module access updated.');
    }

    /**
     * @return list<string>
     */
    private function editablePermissionNames(): array
    {
        return array_keys(array_merge(...array_values(self::PERMISSION_GROUPS)));
    }
}
