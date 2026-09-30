<?php

namespace Tests\Feature;

use App\Models\Admin;
use Database\Seeders\AdminRoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRolePermissionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_super_admin_can_open_sub_admin_permission_editor(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $superAdmin = Admin::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin, 'admin')
            ->get(route('admin.users.permissions.edit'))
            ->assertOk()
            ->assertSee('Sub Admin permissions')
            ->assertSee('View partners')
            ->assertSee('Create partners')
            ->assertSee('Always on');
    }

    public function test_super_admin_can_give_sub_admin_only_partner_module_access(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $superAdmin = Admin::factory()->create();
        $superAdmin->assignRole('Super Admin');
        $subAdmin = Admin::factory()->create();
        $subAdmin->assignRole('Sub Admin');

        $this->actingAs($superAdmin, 'admin')
            ->put(route('admin.users.permissions.update'), [
                'permissions' => [
                    'partners.view',
                    'partners.create',
                    'partners.update',
                    'partners.delete',
                ],
            ])
            ->assertRedirect(route('admin.users.permissions.edit'))
            ->assertSessionHas('status', 'Sub Admin module access updated.');

        $subAdminRole = Role::findByName('Sub Admin', 'admin');
        $this->assertTrue($subAdminRole->hasPermissionTo('admin.access'));
        $this->assertTrue($subAdminRole->hasPermissionTo('partners.view'));
        $this->assertTrue($subAdminRole->hasPermissionTo('partners.create'));
        $this->assertFalse($subAdminRole->hasPermissionTo('partner_centers.view'));
        $this->assertFalse($subAdminRole->hasPermissionTo('admins.manage'));

        $this->actingAs($subAdmin, 'admin')
            ->get(route('admin.partners.index'))
            ->assertOk();

        $this->actingAs($subAdmin, 'admin')
            ->get(route('admin.partner-centers.index'))
            ->assertForbidden();

        $this->actingAs($subAdmin, 'admin')
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_without_admin_management_permission_cannot_edit_sub_admin_access(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.permissions.edit'))
            ->assertForbidden();
    }

    public function test_permission_editor_rejects_unlisted_privileged_permissions(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $superAdmin = Admin::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin, 'admin')
            ->put(route('admin.users.permissions.update'), [
                'permissions' => ['admins.manage'],
            ])
            ->assertSessionHasErrors('permissions.0');

        $this->assertFalse(Role::findByName('Sub Admin', 'admin')->hasPermissionTo('admins.manage'));
    }
}
