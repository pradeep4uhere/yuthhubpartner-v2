<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Database\Seeders\AdminRoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeder_creates_default_super_admin_when_password_is_configured(): void
    {
        Config::set('admin.initial_admin', [
            'name' => 'YouthHub Administrator',
            'email' => 'admin@youthhubpartner.test',
            'password' => 'A-strong-password-123',
        ]);

        $this->seed(AdminRoleSeeder::class);

        $admin = Admin::where('email', 'admin@youthhubpartner.test')->firstOrFail();
        $storedPassword = $admin->password;

        $this->assertSame('YouthHub Administrator', $admin->name);
        $this->assertTrue(Hash::check('A-strong-password-123', $admin->password));
        $this->assertTrue($admin->hasRole('Super Admin'));

        Config::set('admin.initial_admin.password', 'A-different-password-456');
        $this->seed(AdminRoleSeeder::class);

        $this->assertSame($storedPassword, $admin->fresh()->password);
    }

    public function test_seeder_does_not_create_admin_with_an_empty_password(): void
    {
        Config::set('admin.initial_admin.password', '');

        $this->seed(AdminRoleSeeder::class);

        $this->assertDatabaseMissing('admins', [
            'email' => 'admin@youthhubpartner.test',
        ]);
    }

    public function test_admin_dashboard_redirects_guests_to_admin_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_active_admin_can_sign_in_and_open_dashboard(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Admin');

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_inactive_admin_cannot_sign_in(): void
    {
        $admin = Admin::factory()->inactive()->create();

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_partner_account_cannot_sign_in_through_admin_guard(): void
    {
        $user = User::factory()->create();

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_sub_admin_can_open_dashboard_but_cannot_manage_admins(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Sub Admin');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_a_sub_admin_account(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $superAdmin = Admin::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin, 'admin')
            ->get(route('admin.users.index'))
            ->assertSee('Invite an administrator')
            ->assertSee('Current administrators');

        $this->actingAs($superAdmin, 'admin')
            ->post(route('admin.users.store'), [
                'name' => 'New Sub Admin',
                'email' => 'subadmin@example.test',
                'password' => 'A-strong-password-123',
                'password_confirmation' => 'A-strong-password-123',
                'role' => 'Sub Admin',
            ])
            ->assertRedirect(route('admin.users.index'));

        $createdAdmin = Admin::where('email', 'subadmin@example.test')->firstOrFail();
        $this->assertTrue($createdAdmin->hasRole('Sub Admin'));
        $this->assertDatabaseHas('admins', [
            'email' => 'subadmin@example.test',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_cannot_disable_the_last_active_super_admin(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $superAdmin = Admin::factory()->create();
        $superAdmin->assignRole('Super Admin');
        $anotherAdmin = Admin::factory()->create();

        $this->actingAs($superAdmin, 'admin')
            ->patch(route('admin.users.status', $superAdmin))
            ->assertSessionHasErrors('admin');

        $this->actingAs($superAdmin, 'admin')
            ->patch(route('admin.users.status', $anotherAdmin))
            ->assertSessionHas('status', 'Admin account disabled.');

        $this->assertDatabaseHas('admins', [
            'id' => $anotherAdmin->id,
            'is_active' => false,
        ]);
    }
}
