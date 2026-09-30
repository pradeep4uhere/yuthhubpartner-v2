<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\PartnerPlacementUser;
use Database\Seeders\AdminRoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PartnerPlacementUserPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_and_list_a_placement_user(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.partner-placement-users.store'), [
                'name' => 'Ravi Kumar',
                'email' => 'ravi@placement.test',
                'partner_placement_name' => 'Career Connect',
                'contact_number' => '9876543210',
                'pp_code' => 'PP-0001',
                'status' => 'active',
                'password' => 'Strong-placement-password-123',
                'password_confirmation' => 'Strong-placement-password-123',
            ])
            ->assertRedirect(route('admin.partner-placement-users.index'));

        $placementUser = PartnerPlacementUser::where('email', 'ravi@placement.test')->firstOrFail();
        $this->assertDatabaseHas('partner_placement_users', [
            'id' => $placementUser->id,
            'pp_code' => 'PP-0001',
            'status' => 'active',
        ]);
        $this->assertNotSame('Strong-placement-password-123', $placementUser->password);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.partner-placement-users.index'))
            ->assertOk()
            ->assertSee('Career Connect')
            ->assertSee('PP-0001');
    }

    public function test_placement_user_has_separate_login_and_dashboard(): void
    {
        $placementUser = PartnerPlacementUser::factory()->create([
            'password' => 'Test-placement-password-123',
            'name' => 'Placement Contact',
            'partner_placement_name' => 'Career Connect',
        ]);

        $this->post(route('partner-placement.login.store'), [
            'email' => $placementUser->email,
            'password' => 'Test-placement-password-123',
        ])->assertRedirect(route('partner-placement.dashboard'));

        $this->assertAuthenticatedAs($placementUser, 'partner_placement');
        $this->assertGuest('partner');
        $this->assertGuest('partner_center');

        $this->get(route('partner-placement.dashboard'))
            ->assertOk()
            ->assertSee('Placement Contact')
            ->assertSee('Career Connect')
            ->assertSee($placementUser->pp_code);
    }

    public function test_inactive_placement_user_cannot_log_in(): void
    {
        $placementUser = PartnerPlacementUser::factory()->create([
            'status' => 'inactive',
            'password' => 'Test-placement-password-123',
        ]);

        $this->post(route('partner-placement.login.store'), [
            'email' => $placementUser->email,
            'password' => 'Test-placement-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('partner_placement');
    }

    public function test_guest_is_redirected_to_the_placement_login(): void
    {
        $this->get(route('partner-placement.dashboard'))
            ->assertRedirect(route('partner-placement.login'));
    }

    public function test_admin_cannot_delete_placement_user_without_permission(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Admin');
        $placementUser = PartnerPlacementUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.partner-placement-users.destroy', $placementUser))
            ->assertForbidden();

        $this->assertModelExists($placementUser);
    }
}
