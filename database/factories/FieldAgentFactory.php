<?php

namespace Database\Factories;

use App\Models\FieldAgent;
use App\Models\Partner;
use App\Models\PartnerCenter;
use App\Models\PartnerPlacementUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FieldAgent>
 */
class FieldAgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'contact_number' => fake()->numerify('##########'),
            'csc_id' => strtoupper(Str::random(8)),
            'sakhi_id' => strtoupper(Str::random(8)),
            'partner_id' => Partner::factory(),
            'partner_center_id' => PartnerCenter::factory(),
            'placement_partner_user_id' => PartnerPlacementUser::factory(),
            'state' => fake()->state(),
            'district' => fake()->city(),
            'status' => 'active',
            'password' => 'Test-field-agent-password-123',
        ];
    }
}
