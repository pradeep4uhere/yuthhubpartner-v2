<?php

namespace Database\Factories;

use App\Models\PartnerPlacementUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PartnerPlacementUser>
 */
class PartnerPlacementUserFactory extends Factory
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
            'partner_placement_name' => fake()->company(),
            'contact_number' => fake()->numerify('##########'),
            'pp_code' => strtoupper(Str::random(10)),
            'status' => 'active',
            'password' => 'Test-placement-password-123',
        ];
    }
}
