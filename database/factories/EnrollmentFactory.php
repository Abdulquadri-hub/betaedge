<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Tenant;
use App\Models\Student;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'student_id' => Student::factory(),
            'course_id' => Course::factory(),
            'enrolled_at' => fake()->dateTimeThisYear(),
            'completed_at' => null,
            'status' => fake()->randomElement(['active', 'completed', 'dropped']),
            'progress_percentage' => fake()->randomFloat(2, 0, 100),
            'final_grade' => fake()->randomFloat(2, 0, 100),
            'grade_letter' => fake()->randomElement(['A', 'B', 'C', 'D', 'F']),
            'notes' => fake()->paragraph(),
        ];
    }

    public function active(): Factory
    {
        return $this->state(['status' => 'active']);
    }

    public function completed(): Factory
    {
        return $this->state([
            'status' => 'completed',
            'completed_at' => now(),
            'progress_percentage' => 100,
        ]);
    }

    public function dropped(): Factory
    {
        return $this->state(['status' => 'dropped']);
    }
}
