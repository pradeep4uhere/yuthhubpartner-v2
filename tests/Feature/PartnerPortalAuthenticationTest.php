<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PartnerCenter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PartnerPortalAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_partner_can_log_in_and_see_only_its_own_centers(): void
    {
        $partner = Partner::factory()->create([
            'name' => 'North Star Foundation',
            'password' => 'Test-portal-password-123',
        ]);
        Partner::factory()->create(['name' => 'Different Foundation']);
        $center = PartnerCenter::factory()->create([
            'partner_id' => $partner->id,
            'name' => 'North Star Jaipur Center',
        ]);
        PartnerCenter::factory()->create(['name' => 'Unrelated Center']);

        $this->post(route('partner.login.store'), [
            'email' => $partner->email,
            'password' => 'Test-portal-password-123',
        ])->assertRedirect(route('partner.dashboard'));

        $this->assertAuthenticatedAs($partner, 'partner');
        $this->assertGuest('partner_center');

        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee('North Star Foundation')
            ->assertSee('North Star Jaipur Center')
            ->assertDontSee('Different Foundation')
            ->assertDontSee('Unrelated Center');

        $this->assertTrue($partner->centers->contains('id', $center->id));
    }

    public function test_partner_center_can_log_in_and_see_its_parent_partner(): void
    {
        $partner = Partner::factory()->create(['name' => 'North Star Foundation']);
        $center = PartnerCenter::factory()->create([
            'partner_id' => $partner->id,
            'name' => 'North Star Jaipur Center',
            'password' => 'Test-center-password-123',
        ]);

        $this->post(route('partner-center.login.store'), [
            'email' => $center->email,
            'password' => 'Test-center-password-123',
        ])->assertRedirect(route('partner-center.dashboard'));

        $this->assertAuthenticatedAs($center, 'partner_center');
        $this->assertGuest('partner');

        $this->get(route('partner-center.dashboard'))
            ->assertOk()
            ->assertSee('North Star Jaipur Center')
            ->assertSee('North Star Foundation');
    }

    public function test_partner_credentials_cannot_authenticate_a_partner_center(): void
    {
        $partner = Partner::factory()->create([
            'password' => 'Test-portal-password-123',
        ]);

        $this->post(route('partner-center.login.store'), [
            'email' => $partner->email,
            'password' => 'Test-portal-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('partner_center');
    }

    public function test_inactive_partner_cannot_log_in(): void
    {
        $partner = Partner::factory()->create([
            'status' => 'inactive',
            'password' => 'Test-portal-password-123',
        ]);

        $this->post(route('partner.login.store'), [
            'email' => $partner->email,
            'password' => 'Test-portal-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('partner');
    }

    public function test_partner_and_center_guests_redirect_to_their_own_login_pages(): void
    {
        $this->get(route('partner.dashboard'))
            ->assertRedirect(route('partner.login'));

        $this->get(route('partner-center.dashboard'))
            ->assertRedirect(route('partner-center.login'));
    }
}
