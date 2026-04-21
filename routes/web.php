<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\SelectSchoolController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\Instructors\Dashboard\BatchController as InstructorBatchController;
use App\Http\Controllers\Instructors\Dashboard\EarningController as InstructorEarningsController;
use App\Http\Controllers\Instructors\Dashboard\GradingController as InstructorGradingController;
use App\Http\Controllers\Instructors\Dashboard\JobController as InstructorJobController;
use App\Http\Controllers\Instructors\Dashboard\MainController;
use App\Http\Controllers\Instructors\Dashboard\ProfileController as InstructorProfileController;
use App\Http\Controllers\Instructors\Dashboard\SessionController as InstructorSessionController;
use App\Http\Controllers\Instructors\Dashboard\StudentController as InstructorStudentController;
use App\Http\Controllers\Instructors\Dashboard\VerificationController;
use App\Http\Controllers\Instructors\OnboardingController as InstructorsOnboardingController;
use App\Http\Controllers\MarketPlaceController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\Tenant\CourseController as TenantPublicCourseController;
use App\Http\Controllers\Tenant\Dashboard\AcademicLevelController;
use App\Http\Controllers\Tenant\Dashboard\BatchController;
use App\Http\Controllers\Tenant\Dashboard\CertificateController;
use App\Http\Controllers\Tenant\Dashboard\ComplaintController;
use App\Http\Controllers\Tenant\Dashboard\CourseController;
use App\Http\Controllers\Tenant\Dashboard\CourseMaterialController;
use App\Http\Controllers\Tenant\Dashboard\EnrollmentController;
use App\Http\Controllers\Tenant\Dashboard\FinancialController;
use App\Http\Controllers\Tenant\Dashboard\HomeController;
use App\Http\Controllers\Tenant\Dashboard\InstructorController;
use App\Http\Controllers\Tenant\Dashboard\LiveSessionController;
use App\Http\Controllers\Tenant\Dashboard\ParentController;
use App\Http\Controllers\Tenant\Dashboard\ProfileController;
use App\Http\Controllers\Tenant\Dashboard\ReportController;
use App\Http\Controllers\Tenant\Dashboard\SettingController;
use App\Http\Controllers\Tenant\Dashboard\StudentController;
use App\Http\Controllers\Tenant\Dashboard\VerificationController as TenantVerificationController;
use App\Http\Controllers\Tenant\EnrollmentController as PublicEnrollmentController;
use App\Http\Controllers\Tenant\PublicPageController;
use Illuminate\Support\Facades\Route;

// ── Platform domain ────────────────────────────────────────────────────────────

Route::domain(config('app.main_domain'))->middleware(['web'])->group(function () {

    Route::controller(PlatformController::class)->group(function () {
        Route::get('/', 'landing')->name('home');
    });

    Route::controller(MarketPlaceController::class)->group(function () {
        Route::get('/marketplace', 'lists');
    });

    Route::controller(LoginController::class)->group(function () {
        Route::get('/auth/login', 'index')->name('login.index');
        Route::post('/auth/login', 'initiate')->name('login.initiate');
    });

    Route::controller(LogoutController::class)->group(function () {
        Route::post('/auth/logout', 'logout')->name('logout')->middleware('auth');
    });

    Route::controller(PasswordController::class)->group(function () {
        Route::get('/auth/forgot-password', 'showForgot')->name('password.request');
        Route::post('/auth/forgot-password', 'forgot')->name('password.email');
        Route::get('/auth/reset-password', 'showReset')->name('password.reset');
        Route::post('/auth/reset-password', 'reset')->name('password.update');
    });

    Route::controller(SelectSchoolController::class)->group(function () {
        Route::get('/auth/select-school', 'showSelectSchool')->name('school.show');
        Route::post('/auth/select-school', 'selectSchool')->name('school.select');
    });

    Route::controller(EmailVerificationController::class)->group(function () {
        Route::get('/verification/notice', 'notice')->name('verification.notice');
        Route::get('/verification/verify/{token}', 'verify')->name('verification.verify');
        Route::post('/verification/set-password', 'setPassword')->name('password.set');
        Route::post('/verification/resend', 'resend')->middleware('throttle:3,60')->name('verification.resend');
    });

    Route::controller(OnboardingController::class)->middleware(['guest', 'throttle:60,1'])->group(function () {
        Route::get('/onboarding', 'index')->name('onboarding.index');
        Route::post('/onboarding/save', 'save')->name('onboarding.draft');
        Route::post('/onboarding/submit', 'submit')->middleware('throttle:onboarding')->name('onboarding.submit');
        Route::get('/onboarding/status/{jobId}', 'status')->middleware('throttle:onboarding-status')->name('onboarding.status');
    });

    Route::prefix('onboarding')->controller(InstructorsOnboardingController::class)->group(function () {
        Route::get('/instructor', 'index');
    });

    Route::prefix('instructor')->group(function () {
        Route::controller(MainController::class)->group(function () {
            Route::get('', 'index')->name('instructor.home');
            Route::post('/switch-school/{tenantId}', 'switchSchool')->name('instructor.switchSchool');
        });
        Route::prefix('batches')->controller(InstructorBatchController::class)->group(function () {
            Route::get('', 'index')->name('instructor.batches.index');
            Route::get('/{batch}', 'single')->name('instructor.batches.single');
        });
        Route::prefix('sessions')->controller(InstructorSessionController::class)->group(function () {
            Route::get('', 'index')->name('instructor.sessions.index');
        });
        Route::prefix('grading')->controller(InstructorGradingController::class)->group(function () {
            Route::get('', 'index')->name('instructor.grading.index');
            Route::post('/{submission}', 'grade')->name('instructor.grading.grade');
        });
        Route::prefix('students')->controller(InstructorStudentController::class)->group(function () {
            Route::get('', 'index')->name('instructor.students.index');
        });
        Route::prefix('earnings')->controller(InstructorEarningsController::class)->group(function () {
            Route::get('', 'index')->name('instructor.earnings.index');
        });
        Route::prefix('profile')->controller(InstructorProfileController::class)->group(function () {
            Route::get('', 'edit')->name('instructor.profile.edit');
            Route::post('', 'update')->name('instructor.profile.update');
        });
        Route::prefix('applications')->controller(InstructorJobController::class)->group(function () {
            Route::get('', 'index')->name('instructor.applications.index');
            Route::post('/{job}', 'apply')->name('instructor.applications.apply');
        });
        Route::prefix('verification')->controller(VerificationController::class)->group(function () {
            Route::get('', 'index');
        });
    });
});

// ── Tenant subdomains ──────────────────────────────────────────────────────────

Route::domain('{tenant}.' . config('app.main_domain'))->middleware(['web', 'tenant'])->group(function () {

    // Public pages
    Route::controller(PublicPageController::class)->group(function () {
        Route::get('/', 'landing')->name('tenant.landing');
    });

    Route::controller(TenantPublicCourseController::class)->group(function () {
        Route::get('/course/{course}', 'show')->name('tenant.course');
    });

    Route::controller(PublicEnrollmentController::class)->group(function () {
        Route::get('/enroll', 'showEnroll')->name('tenant.enroll');
    });

    // Authenticated dashboard
    Route::prefix('dashboard')->middleware(['tenant.access'])->group(function () {

        Route::controller(HomeController::class)->group(function () {
            Route::get('', 'index');
        });

        // ── Academic Levels (called from Settings) ─────────────────────────────
        Route::controller(AcademicLevelController::class)->group(function () {
            Route::get('/academic-levels', 'list');
            Route::post('/settings/academic-levels', 'store');
            Route::put('/settings/academic-levels/{levelId}', 'update');
            Route::patch('/settings/academic-levels/{levelId}/toggle', 'toggle');
            Route::delete('/settings/academic-levels/{levelId}', 'destroy');
        });

        // ── Courses ────────────────────────────────────────────────────────────
        Route::prefix('courses')->controller(CourseController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.courses.index');
            Route::get('/create', 'create')->name('dashboard.courses.create');
            Route::post('/create', 'save')->name('dashboard.courses.store');
            Route::get('/{courseId}', 'single')->name('dashboard.courses.single');
            Route::get('/{courseId}/edit', 'edit')->name('dashboard.courses.edit');
            Route::post('/{courseId}/edit', 'update')->name('dashboard.courses.update');
            Route::post('/{courseId}/publish', 'publish')->name('dashboard.courses.publish');
            Route::post('/{courseId}/archive', 'archive')->name('dashboard.courses.archive');
            Route::post('/{courseId}/duplicate', 'duplicate')->name('dashboard.courses.duplicate');
            Route::delete('/{courseId}', 'destroy')->name('dashboard.courses.destroy');
        });

        Route::prefix('courses')->controller(CourseMaterialController::class)->group(function () {
            Route::post('/{courseId}/materials', 'store')->name('courses.materials.store');
            Route::delete('/{courseId}/materials/{materialId}', 'destroy')->name('courses.materials.destroy');
        });

        // ── Batches ────────────────────────────────────────────────────────────
        Route::prefix('batches')->controller(BatchController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.batches.index');
            Route::get('/create', 'create')->name('dashboard.batches.create');
            Route::post('/create', 'store')->name('dashboard.batches.store');
            Route::get('/{batchId}', 'single')->name('dashboard.batches.single');
            Route::get('/{batchId}/edit', 'edit')->name('dashboard.batches.edit');
            Route::put('/{batchId}/edit', 'update')->name('dashboard.batches.update');
            Route::patch('/{batchId}/toggle', 'toggleEnrollment')->name('dashboard.batches.toggle');
            Route::post('/{batchId}/publish', 'publish')->name('dashboard.batches.publish');
            Route::delete('/{batchId}', 'delete')->name('dashboard.batches.delete');
        });

        // ── Live Sessions ──────────────────────────────────────────────────────
        Route::prefix('live-sessions')->controller(LiveSessionController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.live-sessions.index');
            Route::post('', 'store')->name('dashboard.live-sessions.store');
            Route::put('/{sessionId}', 'update')->name('dashboard.live-sessions.update');
            Route::post('/{sessionId}/go-live', 'goLive')->name('dashboard.live-sessions.go-live');
            Route::post('/{sessionId}/end', 'endSession')->name('dashboard.live-sessions.end');
            Route::post('/{sessionId}/cancel', 'cancel')->name('dashboard.live-sessions.cancel');
            Route::delete('/{sessionId}', 'destroy')->name('dashboard.live-sessions.destroy');
        });

        // ── Settings ───────────────────────────────────────────────────────────
        Route::prefix('settings')->controller(SettingController::class)->group(function () {
            Route::get('', 'index')->name('settings.show');
            Route::post('/profile', 'updateProfile')->name('settings.profile.update');
            Route::post('/paystack', 'updatePaystack')->name('settings.paystack.update');
            Route::post('/notifications', 'updateNotifications')->name('settings.notifications.update');
        });

        // ── Students ───────────────────────────────────────────────────────────
        Route::prefix('students')->controller(StudentController::class)->group(function () {
            Route::get('', 'index')->name('students.index');
            Route::get('/{student}', 'single')->name('students.single');
            Route::post('/{student}/suspend', 'suspend');
            Route::post('/{student}/activate', 'activate');
        });

        // ── Instructors ────────────────────────────────────────────────────────
        Route::prefix('instructors')->controller(InstructorController::class)->group(function () {
            Route::get('', 'index')->name('instructors.index');
            Route::get('/{instructorId}', 'single');
            Route::post('/invite', 'invite');
            Route::post('/{instructorId}', 'update');
            Route::delete('/{instructorId}', 'destroy');
            Route::post('/{instructorId}/mark-paid', 'markPaid');
        });

        // ── Enrollments ────────────────────────────────────────────────────────
        Route::prefix('enrollments')->controller(EnrollmentController::class)->group(function () {
            Route::get('', 'index');
            Route::patch('/{id}/approve', 'approve');
            Route::patch('/{id}/reject', 'reject');
        });

        // ── Other ──────────────────────────────────────────────────────────────
        Route::prefix('certificates')->controller(CertificateController::class)->group(function () {
            Route::get('', 'index');
        });

        Route::prefix('complaints')->controller(ComplaintController::class)->group(function () {
            Route::get('', 'index');
        });

        Route::prefix('financials')->controller(FinancialController::class)->group(function () {
            Route::get('', 'index');
        });

        Route::prefix('reports')->controller(ReportController::class)->group(function () {
            Route::get('', 'index');
        });

        Route::prefix('parents')->controller(ParentController::class)->group(function () {
            Route::get('', 'index');
            Route::get('/{parentId}', 'single');
            Route::post('/{parentId}/message', 'message');
            Route::post('/{parentId}/thresholds', 'thresholds');
        });

        Route::prefix('profile')->controller(ProfileController::class)->group(function () {
            Route::get('', 'show');
            Route::post('', 'update');
        });

        Route::prefix('verification')->controller(TenantVerificationController::class)->group(function () {
            Route::get('', 'index');
            Route::post('', 'submit');
        });
    });
});