<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\FieldAgent;
use App\Models\Partner;
use App\Models\PartnerCenter;
use App\Models\PartnerPlacementUser;
use Database\Seeders\AdminRoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FieldAgentPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_field_agent_with_related_organizations(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Admin');

        $partner = Partner::factory()->create();
        $partnerCenter = PartnerCenter::factory()->create(['partner_id' => $partner->id]);
        $placementUser = PartnerPlacementUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.field-agents.store'), [
                'name' => 'Asha Field Agent',
                'email' => 'asha@field.test',
                'contact_number' => '9876543210',
                'csc_id' => 'CSC-1001',
                'sakhi_id' => 'SAKHI-2001',
                'partner_id' => $partner->id,
                'partner_center_id' => $partnerCenter->id,
                'placement_partner_user_id' => $placementUser->id,
                'state' => 'Bihar',
                'district' => 'Gaya',
                'status' => 'active',
                'password' => 'Strong-field-agent-password-123',
                'password_confirmation' => 'Strong-field-agent-password-123',
            ])
            ->assertRedirect(route('admin.field-agents.index'));

        $fieldAgent = FieldAgent::where('email', 'asha@field.test')->firstOrFail();
        $this->assertSame($partner->id, $fieldAgent->partner_id);
        $this->assertSame($partnerCenter->id, $fieldAgent->partner_center_id);
        $this->assertSame($placementUser->id, $fieldAgent->placement_partner_user_id);
        $this->assertTrue(Hash::check('Strong-field-agent-password-123', $fieldAgent->password));
    }

    public function test_field_agent_cannot_be_assigned_to_a_center_from_another_partner(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole('Admin');
        $partner = Partner::factory()->create();
        $otherPartner = Partner::factory()->create();
        $otherCenter = PartnerCenter::factory()->create(['partner_id' => $otherPartner->id]);
        $placementUser = PartnerPlacementUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.field-agents.store'), [
                'name' => 'Mismatch Agent',
                'email' => 'mismatch@field.test',
                'partner_id' => $partner->id,
                'partner_center_id' => $otherCenter->id,
                'placement_partner_user_id' => $placementUser->id,
                'state' => 'Bihar',
                'district' => 'Gaya',
                'status' => 'active',
                'password' => 'Strong-field-agent-password-123',
                'password_confirmation' => 'Strong-field-agent-password-123',
            ])
            ->assertSessionHasErrors('partner_center_id');

        $this->assertDatabaseMissing('field_agents', ['email' => 'mismatch@field.test']);
    }

    public function test_field_agent_can_log_in_and_only_see_own_assignment(): void
    {
        $partner = Partner::factory()->create(['name' => 'Field Partner One']);
        $center = PartnerCenter::factory()->create([
            'partner_id' => $partner->id,
            'name' => 'Field Center One',
        ]);
        $placementUser = PartnerPlacementUser::factory()->create([
            'partner_placement_name' => 'Placement Org One',
        ]);
        $fieldAgent = FieldAgent::factory()->create([
            'partner_id' => $partner->id,
            'partner_center_id' => $center->id,
            'placement_partner_user_id' => $placementUser->id,
            'name' => 'Asha Field Agent',
            'password' => 'Test-field-agent-password-123',
        ]);
        FieldAgent::factory()->create([
            'name' => 'Other Field Agent',
            'email' => 'other@field.test',
        ]);

        $this->post(route('field-agent.login.store'), [
            'email' => $fieldAgent->email,
            'password' => 'Test-field-agent-password-123',
        ])->assertRedirect(route('field-agent.dashboard'));

        $this->assertAuthenticatedAs($fieldAgent, 'field_agent');
        $this->assertGuest('partner');
        $this->assertGuest('partner_center');
        $this->assertGuest('partner_placement');

        $this->get(route('field-agent.dashboard'))
            ->assertOk()
            ->assertSee('Asha Field Agent')
            ->assertSee('Field Partner One')
            ->assertSee('Field Center One')
            ->assertSee('Placement Org One')
            ->assertDontSee('Other Field Agent');
    }

    public function test_inactive_field_agent_cannot_log_in(): void
    {
        $fieldAgent = FieldAgent::factory()->create([
            'status' => 'inactive',
            'password' => 'Test-field-agent-password-123',
        ]);

        $this->post(route('field-agent.login.store'), [
            'email' => $fieldAgent->email,
            'password' => 'Test-field-agent-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('field_agent');
    }

    public function test_sub_admin_without_field_agent_view_permission_is_denied(): void
    {
        $this->seed(AdminRoleSeeder::class);
        $subAdmin = Admin::factory()->create();
        $subAdmin->assignRole('Sub Admin');

        $this->actingAs($subAdmin, 'admin')
            ->get(route('admin.field-agents.index'))
            ->assertForbidden();
    }
}
