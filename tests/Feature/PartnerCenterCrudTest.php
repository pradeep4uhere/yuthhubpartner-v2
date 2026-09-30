<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Partner;
use App\Models\PartnerCenter;
use Database\Seeders\AdminRoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PartnerCenterCrudTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_admin_login_from_partner_center_routes(): void
    {
        $this->get(route('admin.partner-centers.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_create_list_and_update_a_center_under_a_partner(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Admin');
        $partner = Partner::factory()->create(['name' => 'North Star Foundation']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.partner-centers.create'))
            ->assertOk()
            ->assertSee('North Star Foundation');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.partner-centers.store'), [
                'partner_id' => $partner->id,
                'partner_code' => 'CENTER-001',
                'name' => 'North Star Jaipur Center',
                'contact_number' => '9876543210',
                'email' => 'jaipur@northstar.test',
                'status' => 'active',
                'password' => 'Strong-center-password-123',
                'password_confirmation' => 'Strong-center-password-123',
            ])
            ->assertRedirect(route('admin.partner-centers.index'));

        $center = PartnerCenter::where('partner_code', 'CENTER-001')->firstOrFail();
        $this->assertSame($partner->id, $center->partner_id);
        $this->assertSame($partner->id, $center->partner->id);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.partner-centers.index', ['partner_id' => $partner->id]))
            ->assertOk()
            ->assertSee('North Star Jaipur Center')
            ->assertSee('North Star Foundation');

        $this->actingAs($admin, 'admin')
            ->put(route('admin.partner-centers.update', $center), [
                'partner_id' => $partner->id,
                'partner_code' => 'CENTER-001',
                'name' => 'North Star Jaipur Hub',
                'contact_number' => '9876543211',
                'email' => 'hub@northstar.test',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('admin.partner-centers.index'));

        $this->assertDatabaseHas('partner_centers', [
            'id' => $center->id,
            'partner_id' => $partner->id,
            'name' => 'North Star Jaipur Hub',
            'status' => 'inactive',
        ]);
    }

    public function test_invalid_partner_id_is_rejected(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.partner-centers.store'), [
                'partner_id' => 999999,
                'partner_code' => 'CENTER-INVALID',
                'name' => 'Unattached Center',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('partner_id');
    }

    public function test_admin_without_delete_permission_cannot_delete_a_center(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Admin');
        $partner = Partner::factory()->create();
        $center = PartnerCenter::factory()->create(['partner_id' => $partner->id]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.partner-centers.destroy', $center))
            ->assertForbidden();

        $this->assertModelExists($center);
    }

    public function test_super_admin_can_delete_a_partner_center(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $superAdmin = Admin::factory()->create();
        $superAdmin->assignRole('Super Admin');
        $partner = Partner::factory()->create();
        $center = PartnerCenter::factory()->create(['partner_id' => $partner->id]);

        $this->actingAs($superAdmin, 'admin')
            ->delete(route('admin.partner-centers.destroy', $center))
            ->assertRedirect(route('admin.partner-centers.index'));

        $this->assertDatabaseMissing('partner_centers', ['id' => $center->id]);
    }

    public function test_sub_admin_cannot_view_partner_centers(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Sub Admin');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.partner-centers.index'))
            ->assertForbidden();
    }

    public function test_partner_with_centers_cannot_be_deleted(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $superAdmin = Admin::factory()->create();
        $superAdmin->assignRole('Super Admin');
        $partner = Partner::factory()->create();
        PartnerCenter::factory()->create(['partner_id' => $partner->id]);

        $this->actingAs($superAdmin, 'admin')
            ->delete(route('admin.partners.destroy', $partner))
            ->assertSessionHasErrors('partner');

        $this->assertModelExists($partner);
    }
}
