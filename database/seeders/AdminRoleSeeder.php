<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminRoleSeeder extends Seeder
{
    /**
     * Create all permissions required by the Admin module routes.
     */
    private function getPermissions(): array
    {
        return [
            // Dashboard
            'admin.access',

            // Learners
            'learners.view',
            'learners.create',
            'learners.update',
            'learners.delete',
            'learners.export',
            'learners.import',
            'learners.sync',

            // Learning
            'learning.manage',

            // Events
            'events.manage',
            'event_masters.view',
            'event_masters.create',
            'event_masters.update',
            'event_masters.delete',
            'event_categories.view',
            'event_categories.create',
            'event_categories.update',
            'event_categories.delete',
            'event_types.view',
            'event_types.create',
            'event_types.update',
            'event_types.delete',

            // Event Transactions
            'event_transactions.view',
            'event_transactions.update',
            'event_transactions.review',
            'event_transactions.approve',
            'event_transactions.reject',

            // Placements
            'placements.manage',

            // Verification
            'verification.manage',

            // Reports
            'reports.export',

            // Admin Users
            'admins.manage',

            // Partners
            'partners.view',
            'partners.create',
            'partners.update',
            'partners.delete',

            // Partner Centers
            'partner_centers.view',
            'partner_centers.create',
            'partner_centers.update',
            'partner_centers.delete',

            // Placement Partner Users
            'partner_placement_users.view',
            'partner_placement_users.create',
            'partner_placement_users.update',
            'partner_placement_users.delete',

            // Field Agents
            'field_agents.view',
            'field_agents.create',
            'field_agents.update',
            'field_agents.delete',
            'field_agents.export',

            // Downloads
            'downloads.view',
            'downloads.download',
            'downloads.process',

            // Logs
            'logs.view',
            'logs.clear',

            // Support Tickets
            'support-tickets.view',
            'support-tickets.reply',
            'support-tickets.internal-notes',
            'support-tickets.manage',
            'support-tickets.assign',
            'support-tickets.categories.manage',

            // Announcements
            'announcements.view',
            'announcements.create',
            'announcements.update',
            'announcements.publish',
            'announcements.delete',
        ];
    }

    /**
     * Run the Admin role and permission seeder.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create all permissions using the admin guard.
        foreach ($this->getPermissions() as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }

        // Create or retrieve the Admin roles.
        $superAdminRole = Role::findOrCreate('Super Admin', 'admin');
        $adminRole = Role::findOrCreate('Admin', 'admin');
        $subAdminRole = Role::findOrCreate('Sub Admin', 'admin');

        // Give every admin permission to the Super Admin role.
        $superAdminRole->syncPermissions(
            Permission::where('guard_name', 'admin')->get()
        );

        // Define permissions available to the Admin role.
        $adminPermissions = [
            'admin.access',

            'learners.view',
            'learners.create',
            'learners.update',
            'learners.delete',
            'learners.export',
            'learners.import',
            'learners.sync',

            'learning.manage',
            'events.manage',

            'event_masters.view',
            'event_masters.create',
            'event_masters.update',
            'event_masters.delete',

            'event_categories.view',
            'event_categories.create',
            'event_categories.update',
            'event_categories.delete',

            'event_types.view',
            'event_types.create',
            'event_types.update',
            'event_types.delete',

            'event_transactions.view',
            'event_transactions.update',
            'event_transactions.review',
            'event_transactions.approve',
            'event_transactions.reject',

            'placements.manage',
            'verification.manage',
            'reports.export',

            'partners.view',
            'partners.create',
            'partners.update',

            'partner_centers.view',
            'partner_centers.create',
            'partner_centers.update',

            'partner_placement_users.view',
            'partner_placement_users.create',
            'partner_placement_users.update',

            'field_agents.view',
            'field_agents.create',
            'field_agents.update',
            'field_agents.export',

            'downloads.view',
            'downloads.download',
            'downloads.process',

            'logs.view',

            'support-tickets.view',
            'support-tickets.reply',
            'support-tickets.internal-notes',
            'support-tickets.manage',
            'support-tickets.assign',
            'support-tickets.categories.manage',

            'announcements.view',
            'announcements.create',
            'announcements.update',
            'announcements.publish',
            'announcements.delete',
        ];

        $adminRole->syncPermissions($adminPermissions);

        // Define limited permissions available to the Sub Admin role.
        $subAdminPermissions = [
            'admin.access',
            'learners.view',
            'support-tickets.view',
            'support-tickets.reply',
            'support-tickets.internal-notes',
            'support-tickets.manage',
            'announcements.view',
        ];

        $subAdminRole->syncPermissions($subAdminPermissions);

        // Create the initial Super Admin account when credentials are configured.
        $initialAdmin = config('admin.initial_admin');

        if (blank($initialAdmin['password'] ?? null)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return;
        }

        $admin = Admin::firstOrCreate(
            ['email' => $initialAdmin['email']],
            [
                'name' => $initialAdmin['name'],
                'password' => $initialAdmin['password'],
                'is_active' => true,
            ]
        );

        // Assign the Super Admin role to the initial administrator.
        $admin->syncRoles($superAdminRole);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
