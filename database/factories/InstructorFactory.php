<?php

namespace Database\Factories;

use App\Models\Instructor;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InstructorFactory extends Factory
{
    protected $model = Instructor::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory()->instructor(),
            'instructor_id' => 'INS' . strtoupper(Str::random(8)),
            'qualification' => fake()->randomElement(['Bachelor', 'Master', 'PhD', 'Diploma']),
            'specialization' => fake()->jobTitle(),
            'years_of_experience' => fake()->numberBetween(0, 40),
            'bio' => fake()->paragraph(),
            'linkedin_url' => fake()->url(),
            'hourly_rate' => fake()->randomFloat(2, 25, 150),
            'employment_type' => fake()->randomElement(['full_time', 'part_time', 'contract']),
            'hire_date' => fake()->dateTimeThisYear(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'average_rating' => fake()->randomFloat(2, 3, 5),
            'total_reviews' => fake()->numberBetween(0, 100),
        ];
    }

    public function fullTime(): Factory
    {
        return $this->state(['employment_type' => 'full_time']);
    }

    public function partTime(): Factory
    {
        return $this->state(['employment_type' => 'part_time']);
    }

    public function active(): Factory
    {
        return $this->state(['status' => 'active']);
    }

    public function inactive(): Factory
    {
        return $this->state(['status' => 'inactive']);
    }
}
