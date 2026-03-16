<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Tenant;
use App\Models\AcademicLevel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = fake()->catchPhrase();

        return [
            'tenant_id' => Tenant::factory(),
            'academic_level_id' => AcademicLevel::factory(),
            'course_code' => strtoupper(Str::random(4)) . fake()->numberBetween(101, 999),
            'title' => 'Introduction to ' . $title,
            'description' => fake()->paragraph(3),
            'category' => fake()->randomElement(['Science', 'Mathematics', 'Languages', 'Technology', 'Arts']),
            'level' => fake()->randomElement(['Beginner', 'Intermediate', 'Advanced']),
            'duration_weeks' => fake()->numberBetween(4, 16),
            'credit_hours' => fake()->randomFloat(2, 1, 6),
            'price' => fake()->randomFloat(2, 99, 999),
            'thumbnail' => fake()->imageUrl(400, 300, 'education'),
            'learning_objectives' => json_encode([
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
            ]),
            'prerequisites' => json_encode(['None', 'Basic Math', 'English Proficiency']),
            'status' => 'active',
            'max_students' => fake()->numberBetween(20, 100),
            'is_published' => true,
            'published_at' => now(),
        ];
    }

    public function draft(): Factory
    {
        return $this->state([
            'status' => 'draft',
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function archived(): Factory
    {
        return $this->state(['status' => 'archived']);
    }
}
