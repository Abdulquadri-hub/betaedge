<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Student;
use App\Models\Instructor;
use App\Models\AcademicLevel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Assignment;
use App\Models\ClassSession;
use App\Models\Material;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\ParentModel;
use App\Models\TenantUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * DatabaseSeeder
 *
 * Main seeder that creates realistic test data for development
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $plans = [
            SubscriptionPlan::factory()->starter()->create(),
            SubscriptionPlan::factory()->professional()->create(),
            SubscriptionPlan::factory()->enterprise()->create(),
        ];


        $adminUser = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@betaedge.com',
            'password' => bcrypt('password'),
        ]);

        $this->createTenantsWithData($plans);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin email: admin@betaedge.com');
        $this->command->info('Password: password');
    }


    private function createTenantsWithData(array $plans): void
    {
        for ($i = 1; $i <= 2; $i++) {
            $this->createSingleTenant($plans, $i);
        }
    }

    private function createSingleTenant(array $plans, int $tenantNumber): void
    {
        // Create owner user
        $ownerUser = User::factory()->admin()->create([
            'name' => "School Owner $tenantNumber",
            'email' => "owner$tenantNumber@school.com",
        ]);

        // Create tenant
        $tenant = Tenant::factory()->create([
            'owner_id' => $ownerUser->id,
            'owner_email' => $ownerUser->email,
            'name' => "Test School $tenantNumber",
            'slug' => "test-school-$tenantNumber",
        ]);

        // Add owner to tenant users
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $ownerUser->id,
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        // Create subscription for tenant
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plans[0]->id,
        ]);

        $academicLevels = AcademicLevel::factory(3)->create(['tenant_id' => $tenant->id]);


        $instructorUsers = User::factory(5)->instructor()->create();
        $instructors = collect();
        foreach ($instructorUsers as $user) {
            $instructor = Instructor::factory()->active()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
            ]);
            $instructors->push($instructor);

            TenantUser::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => 'instructor',
                'status' => 'active',
                'joined_at' => now(),
            ]);
        }

        $courses = Course::factory(6)->create([
            'tenant_id' => $tenant->id,
            'academic_level_id' => $academicLevels->first()->id,
        ]);

        foreach ($courses as $course) {
            // Attach instructors
            $course->instructors()->attach(
                $instructors->random(2)->pluck('id'),
                ['assigned_date' => now(), 'is_primary_instructor' => true]
            );

            // Create materials
            Material::factory(3)->create([
                'tenant_id' => $tenant->id,
                'course_id' => $course->id,
            ]);

            // Create assignments per instructor
            foreach ($instructors->random(2) as $instructor) {
                Assignment::factory(2)->create([
                    'tenant_id' => $tenant->id,
                    'course_id' => $course->id,
                    'instructor_id' => $instructor->id,
                ]);
            }

            // Create class sessions
            ClassSession::factory(3)->create([
                'tenant_id' => $tenant->id,
                'course_id' => $course->id,
                'instructor_id' => $instructors->random()->id,
            ]);
        }

        $studentUsers = User::factory(15)->student()->create();

        $students = collect();

        foreach ($studentUsers as $user) {
            $student = Student::factory()->active()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'academic_level_id' => $academicLevels->random()->id,
            ]);
            $students->push($student);

            TenantUser::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => 'student',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            if (rand(1, 2) === 1) {
                $parentUser = User::factory()->parent()->create();
                $parent = ParentModel::factory()->create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $parentUser->id,
                ]);

                $student->parents()->attach($parent, [
                    'relationship' => 'father',
                    'is_primary_contact' => true,
                    'can_view_grades' => true,
                    'can_view_attendance' => true,
                ]);

                TenantUser::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $parentUser->id,
                    'role' => 'parent',
                    'status' => 'active',
                    'joined_at' => now(),
                ]);
            }
        }

        foreach ($students as $student) {
            $enrollmentCourses = $courses->random(rand(2, 4));
            foreach ($enrollmentCourses as $course) {
                Enrollment::factory()->active()->create([
                    'tenant_id' => $tenant->id,
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                ]);
            }
        }

        $this->command->info("enant '{$tenant->name}' created with:");
        $this->command->info("   - {$students->count()} students");
        $this->command->info("   - {$instructors->count()} instructors");
        $this->command->info("   - {$courses->count()} courses");
    }
}
