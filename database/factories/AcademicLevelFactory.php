<?php

namespace Database\Factories;

use App\Models\AcademicLevel;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AcademicLevelFactory extends Factory
{
    protected $model = AcademicLevel::class;

    public function definition(): array
    {
        $levels = [
            ['name' => 'Primary', 'code' => 'PRIMARY', 'number' => 1],
            ['name' => 'Secondary JSS', 'code' => 'JSS', 'number' => 2],
            ['name' => 'Secondary SS', 'code' => 'SS', 'number' => 3],
            ['name' => 'University', 'code' => 'UNIV', 'number' => 4],
            ['name' => 'Postgraduate', 'code' => 'POSTGRAD', 'number' => 5],
        ];
        
        $level = fake()->randomElement($levels);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $level['name'],
            'code' => $level['code'],
            'level_number' => $level['number'],
            'description' => fake()->sentence(),
            'is_active' => true,
            'display_order' => $level['number'],
        ];
    }
}
