<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Models\AcademicLevel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory()->student(),
            'student_id' => 'STU' . strtoupper(Str::random(8)),
            'date_of_birth' => fake()->dateTimeBetween('-25 years', '-6 years'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'enrollment_date' => fake()->dateTimeThisYear(),
            'enrollment_status' => fake()->randomElement(['active', 'inactive', 'graduated']),
            'notes' => fake()->paragraph(),
            'academic_level_id' => AcademicLevel::factory(),
            'gpa' => fake()->randomFloat(2, 0, 4),
        ];
    }

    public function active(): Factory
    {
        return $this->state(['enrollment_status' => 'active']);
    }

    public function inactive(): Factory
    {
        return $this->state(['enrollment_status' => 'inactive']);
    }

    public function graduated(): Factory
    {
        return $this->state(['enrollment_status' => 'graduated']);
    }
}
