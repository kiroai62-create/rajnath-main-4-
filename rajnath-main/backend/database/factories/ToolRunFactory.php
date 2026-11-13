<?php

namespace Database\Factories;

use App\Models\ToolRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ToolRun>
 */
class ToolRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tool' => 'numerology',
            'channel' => 'web',
            'visitor' => fake()->uuid(),
            'inputs' => ['name' => fake()->name(), 'birth_date' => fake()->date('Y-m-d', '-20 years')],
            'summary' => 'Mulank '.fake()->numberBetween(1, 9),
            'country' => 'IN',
            'device' => 'mobile',
            'locale' => 'en',
        ];
    }
}
