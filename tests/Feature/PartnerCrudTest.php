<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Partner;
use Database\Seeders\AdminRoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PartnerCrudTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_admin_login_from_partner_routes(): void
    {
        $this->get(route('admin.partners.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_list_search_create_and_update_partners(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Admin');
        $partner = Partner::factory()->create([
            'name' => 'North Star Foundation',
            'partner_code' => 'PARTNER-001',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.partners.index', ['search' => 'North Star']))
            ->assertOk()
            ->assertSee('North Star Foundation')
            ->assertSee('PARTNER-001');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.partners.store'), [
                'partner_code' => 'PARTNER-002',
                'name' => 'Bright Path Trust',
                'contact_number' => '9876543210',
                'email' => 'hello@brightpath.test',
                'status' => 'active',
                'password' => 'Strong-partner-password-123',
                'password_confirmation' => 'Strong-partner-password-123',
            ])
            ->assertRedirect(route('admin.partners.index'));

        $this->assertDatabaseHas('partners', [
            'partner_code' => 'PARTNER-002',
            'name' => 'Bright Path Trust',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.partners.update', $partner), [
                'partner_code' => 'PARTNER-001',
                'name' => 'North Star Foundation Updated',
                'contact_number' => '9876543211',
                'email' => 'contact@northstar.test',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('admin.partners.index'));

        $this->assertDatabaseHas('partners', [
            'id' => $partner->id,
            'name' => 'North Star Foundation Updated',
            'status' => 'inactive',
        ]);
    }

    public function test_duplicate_partner_code_is_rejected(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Admin');
        Partner::factory()->create(['partner_code' => 'DUPLICATE-01']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.partners.store'), [
                'partner_code' => 'DUPLICATE-01',
                'name' => 'Duplicate Partner',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('partner_code');
    }

    public function test_admin_without_delete_permission_cannot_delete_partner(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Admin');
        $partner = Partner::factory()->create();

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.partners.destroy', $partner))
            ->assertForbidden();

        $this->assertModelExists($partner);
    }

    public function test_super_admin_can_delete_partner(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $superAdmin = Admin::factory()->create();
        $superAdmin->assignRole('Super Admin');
        $partner = Partner::factory()->create();

        $this->actingAs($superAdmin, 'admin')
            ->delete(route('admin.partners.destroy', $partner))
            ->assertRedirect(route('admin.partners.index'));

        $this->assertDatabaseMissing('partners', ['id' => $partner->id]);
    }

    public function test_sub_admin_cannot_view_partners(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Sub Admin');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.partners.index'))
            ->assertForbidden();
    }
}
