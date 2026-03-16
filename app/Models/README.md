# BetaEdge Eloquent Models Implementation Guide

## Overview

This directory contains 40+ comprehensive Eloquent models for the BetaEdge multi-tenant EdTech application built with Laravel 12 and MySQL.

## Architecture

### Multi-Tenancy
- All models with `tenant_id` use the `BelongsToTenant` trait
- Ensures data isolation across tenants
- Applies automatic tenant scoping to queries

### Model Categories

#### 1. Core Academic (13 models)
- **Student**: Learner profiles with enrollment tracking
- **Instructor**: Teacher information with course assignments
- **Course**: Course offerings with academic levels
- **AcademicLevel**: Grade/level definitions (Grade 9, 10, etc.)
- **Enrollment**: Student-Course associations
- **ClassSession**: Individual class/lecture sessions
- **Material**: Course learning materials
- **Assignment**: Course assignments
- **Submission**: Student assignment submissions
- **Grade**: Assignment grades with feedback
- **Attendance**: Session attendance records
- **InstructorAttendance**: Teacher attendance tracking
- **Report**: Generated academic/system reports

#### 2. Parent/Guardian (5 models)
- **ParentModel**: Guardian profiles
- **StudentParent** (Pivot): Link parents to students with relationship info
- **ChildLinkingRequest**: Parent-student verification requests
- **ParentRegistration**: Parent self-registration
- **ParentAssignment**: Track parent visibility of assignments

#### 3. Multi-Tenancy (6 models)
- **TenantUser**: User roles within tenants
- **TenantInvitation**: Invite users to join tenant
- **DomainVerification**: Custom domain verification
- **TenantPage**: Custom tenant pages
- **TenantFile**: File storage and management
- **OnboardingProcess**: Tenant onboarding tracking

#### 4. Billing (7 models)
- **SubscriptionPlan**: Global subscription tier definitions
- **Subscription**: Tenant subscriptions
- **TenantPaymentConfig**: Payment provider configuration
- **TenantPayment**: Subscription payments
- **Payment**: Student/parent payments
- **Notification**: System notifications
- **UserNotification** (Pivot): User-notification with read status

#### 5. Marketplace (3 models)
- **MarketplaceListing**: Marketplace entries
- **MarketplaceReview**: Review ratings
- **MarketplaceClick**: Click analytics

#### 6. Enrollment & Progression (2 models)
- **EnrollmentRequest**: Enrollment requests
- **StudentPromotion**: Grade promotions

#### 7. Admin & Platform (1 model)
- **Admin**: Admin user roles

#### 8. Analytics & Infrastructure (3 models)
- **TenantAnalytic**: Tenant analytics data
- **JobProgress**: Background job tracking
- **ActivityLog**: Audit trail logging

## Common Model Features

### Traits
```php
use BelongsToTenant;      // Auto-scopes to current tenant
use SoftDeletes;          // Soft delete support
```

### Standard Casts
```php
protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
    'is_active' => 'boolean',
    'price' => 'decimal:2',
    'data' => 'array',
];
```

### Standard Relationships
- Return type hints on all relationship methods
- Proper use of `belongsTo`, `hasMany`, `belongsToMany`
- Pivot data with `withPivot()` for many-to-many
- Timestamps with `withTimestamps()`

### Standard Scopes Pattern
```php
public function scopeActive($query)
{
    return $query->where('is_active', true);
}
```

### Standard Helper Methods
- Calculation methods (totals, averages, rates)
- Status checking methods (isActive, isPassing)
- Action methods (mark*, update*, notify*)
- Data retrieval methods (get*, calculate*)

## Key Traits

### BelongsToTenant
Located in `app/Traits/BelongsToTenant.php`

Automatically:
- Adds `tenant_id` to queries in `whereHasBelongsToTenant` scope
- Ensures only tenant-scoped data is returned
- Must be used on all models with `tenant_id`

## Relationships Overview

### Core Academic Relationships
```
Student ←→ AcademicLevel
Student ←→ User
Student ←→ ParentModel (many-to-many)
Student → Enrollment → Course
Student → Attendance
Student → Submission → Grade

Instructor ←→ User
Instructor ↔️ Course (many-to-many)
Instructor → Assignment
Instructor → ClassSession
Instructor → Grade

Course ←→ AcademicLevel
Course ↔️ Instructor (many-to-many)
Course → Material
Course → Assignment
Course → ClassSession
Course → Enrollment

Enrollment → Student
Enrollment → Course
Enrollment → Submission
Enrollment → Attendance
Enrollment → Grade

Assignment → Course
Assignment → Instructor
Assignment → Submission

Submission → Assignment
Submission → Student
Submission → Grade

Grade ← Submission
Grade ← Instructor

ClassSession → Course
ClassSession → Instructor
ClassSession → Attendance

Attendance → Student
Attendance → Course
Attendance → ClassSession
```

### Parent/Guardian Relationships
```
ParentModel ←→ User
ParentModel ↔️ Student (many-to-many via StudentParent)
ParentModel → ChildLinkingRequest
ParentModel → ParentAssignment

ChildLinkingRequest → Student
ChildLinkingRequest → ParentModel

StudentParent (Pivot)
- relationship: father/mother/guardian
- is_primary_contact: boolean
- can_view_grades: boolean
- can_view_attendance: boolean
```

### Billing Relationships
```
Subscription → SubscriptionPlan
Subscription → TenantPayment

TenantPayment ← Subscription

Payment → Student
Payment → ParentModel
Payment → Course

Notification ↔️ User (many-to-many via UserNotification)

UserNotification (Pivot)
- read_at: nullable datetime
- is_archived: boolean
```

## Scopes Reference

### Academic Scopes
- `Student`: active(), inactive(), suspended(), byLevel(), byGender()
- `Course`: active(), published(), byLevel(), byCategory()
- `Enrollment`: active(), completed(), pending(), withdrawn(), failed()
- `Attendance`: present(), absent(), late(), excused()
- `Grade`: published(), unpublished()
- `Assignment`: active(), published(), overdue()

### Billing Scopes
- `Subscription`: active(), cancelled(), expiring()
- `Payment`: pending(), completed(), failed(), byStudent()

### Status Scopes
- Most models: active(), inactive()

## Helper Methods Reference

### Calculation Methods
```php
// Students
$student->calculateGPA()
$student->getAttendancePercentage()
$student->calculateGPA()

// Enrollments
$enrollment->getAttendanceRate()
$enrollment->getAverageSubmissionScore()
$enrollment->updateProgress()

// Instructors
$instructor->calculateMonthlyHours($month, $year)
$instructor->calculateMonthlyEarnings($month, $year)

// Courses
$course->getEnrollmentCount()
$course->getActiveStudentCount()
```

### Status Methods
```php
$student->isInGoodStanding()
$student->isAdult()
$student->isMinor()

$enrollment->isPassing()
$enrollment->updateProgress()

$grade->isExcellent()
$grade->isPassing()
```

### Action Methods
```php
$enrollment->markCompleted($finalGrade)
$enrollment->markWithdrawn()

$student->promote($toLevelId, $promotedBy)
$student->canBePromoted()

$grade->publish()
$grade->unpublish()
```

## Usage Examples

### Query Examples
```php
// Get active students at a specific level
$students = Student::active()
    ->byLevel($levelId)
    ->with('academicLevel', 'user')
    ->paginate();

// Get student's grades
$grades = $student->grades()->where('is_published', true)->get();

// Get course's average score
$avgScore = $course->enrollments()
    ->with('grade')
    ->avg('final_grade');

// Get pending enrollment requests
$requests = EnrollmentRequest::where('status', 'pending')
    ->with('student', 'course')
    ->paginate();
```

### CRUD Examples
```php
// Create enrollment
$enrollment = Enrollment::create([
    'tenant_id' => auth()->user()->tenant_id,
    'student_id' => $student->id,
    'course_id' => $course->id,
    'status' => 'active',
]);

// Update enrollment progress
$enrollment->update(['progress_percentage' => 75]);

// Mark grade as published
$grade->update(['is_published' => true]);

// Promote student
$promotion = $student->promote($newLevelId, auth()->id());
```

## Soft Deletes

Models with soft deletes:
- Student
- Course
- Enrollment
- Assignment
- Submission
- TenantFile
- TenantPaymentConfig
- TenantPayment
- Payment
- Subscription

Querying soft-deleted records:
```php
$deleted = Model::onlyTrashed()->get();
$all = Model::withTrashed()->get();
$restored = Model::restore();
```

## Database Relationships (Pivot Tables)

### student_parent
```php
$table->foreignId('student_id')->constrained();
$table->foreignId('parent_id')->constrained('parent_models');
$table->string('relationship'); // father, mother, guardian
$table->boolean('is_primary_contact')->default(false);
$table->boolean('can_view_grades')->default(true);
$table->boolean('can_view_attendance')->default(true);
$table->timestamps();
```

### instructor_course
```php
$table->foreignId('instructor_id')->constrained();
$table->foreignId('course_id')->constrained();
$table->timestamp('assigned_date')->useCurrent();
$table->boolean('is_primary_instructor')->default(false);
$table->timestamps();
```

### user_notifications
```php
$table->foreignId('user_id')->constrained();
$table->foreignId('notification_id')->constrained();
$table->timestamp('read_at')->nullable();
$table->boolean('is_archived')->default(false);
$table->timestamps();
```

## Best Practices

1. **Always use type hints** on relationship methods
2. **Use scopes** for common queries instead of repeating conditions
3. **Include helper methods** for business logic
4. **Document relationships** clearly in comments
5. **Use proper casts** for dates, decimals, booleans
6. **Eager load relationships** with `with()` to avoid N+1 queries
7. **Use SoftDeletes** for audit trail requirements
8. **Return proper types** from helper methods

## Performance Optimization

### Eager Loading
```php
$students = Student::with(['user', 'academicLevel', 'enrollments.course'])
    ->active()
    ->paginate();
```

### Select Specific Columns
```php
$students = Student::select('id', 'user_id', 'student_id', 'gpa')
    ->active()
    ->get();
```

### Chunk Processing for Large Datasets
```php
Student::chunk(100, function ($students) {
    foreach ($students as $student) {
        $student->updateGPA();
    }
});
```

## File Locations

All models are located in: `app/Models/`

- Traits: `app/Traits/BelongsToTenant.php`
- Scopes: Defined within model classes (see `app/Models/Scopes/` if extracted)
- Migrations: `database/migrations/`

## Related Files

- Model Specification: `app/Models/MODELS_SPECIFICATION.php`
- Trait Definition: `app/Traits/BelongsToTenant.php`
- Models Factory: `database/factories/`
- Seeders: `database/seeders/`

---

**Last Updated**: Laravel 12
**Database**: MySQL
**Multi-Tenancy**: Yes (tenant_id on all application models)
