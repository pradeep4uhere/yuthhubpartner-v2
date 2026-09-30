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
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'admin.access',
            'learners.view',
            'learners.update',
            'learners.create',
            'learning.manage',
            'events.manage',
            'placements.manage',
            'verification.manage',
            'reports.export',
            'admins.manage',
            'partners.view',
            'partners.create',
            'partners.update',
            'partners.delete',
            'partner_centers.view',
            'partner_centers.create',
            'partner_centers.update',
            'partner_centers.delete',
            'partner_placement_users.view',
            'partner_placement_users.create',
            'partner_placement_users.update',
            'partner_placement_users.delete',
            'field_agents.view',
            'field_agents.create',
            'field_agents.update',
            'field_agents.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }

        $superAdminRole = Role::findOrCreate('Super Admin', 'admin');
        $adminRole = Role::findOrCreate('Admin', 'admin');
        $subAdminRole = Role::findOrCreate('Sub Admin', 'admin');

        $superAdminRole->syncPermissions(Permission::where('guard_name', 'admin')->get());
        $adminRole->syncPermissions([
            'admin.access',
            'learners.view',
            'learners.update',
            'learners.create',
            'learning.manage',
            'events.manage',
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
        ]);
        $subAdminRole->syncPermissions([
            'admin.access',
            'learners.view',
        ]);

        $initialAdmin = config('admin.initial_admin');

        if (blank($initialAdmin['password'])) {
            return;
        }

        $admin = Admin::firstOrCreate(
            ['email' => $initialAdmin['email']],
            [
                'name' => $initialAdmin['name'],
                'password' => $initialAdmin['password'],
                'is_active' => true,
            ],
        );

        $admin->syncRoles($superAdminRole);
    }
}
