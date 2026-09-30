<?php

namespace Database\Factories;

use App\Models\Partner;
use App\Models\PartnerCenter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PartnerCenter>
 */
class PartnerCenterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'partner_id' => Partner::factory(),
            'partner_code' => strtoupper(Str::random(10)),
            'name' => fake()->company().' Center',
            'contact_number' => fake()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
            'password' => 'Test-portal-password-123',
        ];
    }
}
