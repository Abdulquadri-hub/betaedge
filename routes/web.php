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
use App\Http\Controllers\Tenant\Dashboard\AcademicLevelController;
use App\Http\Controllers\Tenant\Dashboard\AttendanceController;
use App\Http\Controllers\Tenant\Dashboard\BatchController;
use App\Http\Controllers\Tenant\Dashboard\CertificateController;
use App\Http\Controllers\Tenant\Dashboard\ComplaintController;
use App\Http\Controllers\Tenant\Dashboard\CourseController;
use App\Http\Controllers\Tenant\Dashboard\CourseMaterialController;
use App\Http\Controllers\Tenant\Dashboard\EnrollmentController as DashboardEnrollmentController;
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
use App\Http\Controllers\Tenant\EnrollmentController;
use App\Http\Controllers\Tenant\PublicBatchController;
use App\Http\Controllers\Tenant\PublicPageController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

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


Route::domain('{tenant}.' . config('app.main_domain'))->middleware(['web', 'tenant'])->group(function () {

    Route::controller(PublicPageController::class)->group(function () {
        Route::get('/', 'landing')->name('tenant.landing');
    });

    Route::prefix('batches')->group(function () {

        Route::controller(PublicBatchController::class)->group(function () {
            Route::get('', 'index')->name('tenant.batches.index');
            Route::get('/{batchSlug}', 'show')->name('tenant.batches.show');
        });

        Route::prefix('/{batchSlug}')->controller(EnrollmentController::class)->group(function () {
            Route::get('/enroll', 'showForm')->name('tenant.enroll');
            Route::post('/enroll', 'submit')->name('tenant.enroll.submit')->middleware('throttle:10,1');
            Route::get('/payment/callback', 'paystackCallback')->name('tenant.payment.callback');
        });
    });

    Route::post('/webhooks/paystack', [EnrollmentController::class, 'paystackWebhook'])
        ->name('tenant.webhook.paystack')
        ->withoutMiddleware(['web', 'tenant', VerifyCsrfToken::class]);

    Route::get('/verify/{certificateCode}', [CertificateController::class, 'verify'])
        ->name('tenant.certificate.verify');

    Route::prefix('dashboard')->middleware(['tenant.access'])->group(function () {

        Route::controller(HomeController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.home');
        });

        Route::controller(AcademicLevelController::class)->group(function () {
            Route::get('/academic-levels', 'list')->name('dashboard.academic-levels.list');
            Route::post('/settings/academic-levels', 'store')->name('dashboard.academic-levels.store');
            Route::put('/settings/academic-levels/{levelId}', 'update')->name('dashboard.academic-levels.update');
            Route::patch('/settings/academic-levels/{levelId}/toggle', 'toggle')->name('dashboard.academic-levels.toggle');
            Route::delete('/settings/academic-levels/{levelId}', 'destroy')->name('dashboard.academic-levels.destroy');
        });


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

        Route::prefix('live-sessions')->controller(LiveSessionController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.live-sessions.index');
            Route::post('', 'store')->name('dashboard.live-sessions.store');
            Route::put('/{sessionId}', 'update')->name('dashboard.live-sessions.update');
            Route::post('/{sessionId}/go-live', 'goLive')->name('dashboard.live-sessions.go-live');
            Route::post('/{sessionId}/end', 'endSession')->name('dashboard.live-sessions.end');
            Route::post('/{sessionId}/cancel', 'cancel')->name('dashboard.live-sessions.cancel');
            Route::delete('/{sessionId}', 'destroy')->name('dashboard.live-sessions.destroy');
        });

        Route::prefix('attendance')->controller(AttendanceController::class)->group(function () {
            Route::get('/session/{sessionId}', 'show')->name('dashboard.attendance.show');
            Route::post('/session/{sessionId}', 'save')->name('dashboard.attendance.save');
            Route::get('/batch/{batchId}', 'batchReport')->name('dashboard.attendance.batch-report');
        });

        Route::prefix('students')->controller(StudentController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.students.index');
            Route::get('/{studentId}', 'single')->name('dashboard.students.single');
            Route::post('/{studentId}/suspend', 'suspend')->name('dashboard.students.suspend');
            Route::post('/{studentId}/activate', 'activate')->name('dashboard.students.activate');
        });

        Route::prefix('instructors')->controller(InstructorController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.instructors.index');
            Route::get('/{instructorId}', 'single')->name('dashboard.instructors.single');
            Route::post('/invite', 'invite')->name('dashboard.instructors.invite');
            Route::put('/{instructorId}', 'update')->name('dashboard.instructors.update');
            Route::delete('/{instructorId}', 'destroy')->name('dashboard.instructors.destroy');
            Route::post('/{instructorId}/mark-paid', 'markPaid')->name('dashboard.instructors.mark-paid');
        });

        Route::prefix('enrollments')->controller(DashboardEnrollmentController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.enrollments.index');
            Route::patch('/{id}/approve', 'approve')->name('dashboard.enrollments.approve');
            Route::patch('/{id}/reject', 'reject')->name('dashboard.enrollments.reject');
        });

        Route::prefix('certificates')->controller(CertificateController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.certificates.index');
            Route::post('/batch/{batchId}/generate', 'generateForBatch')->name('dashboard.certificates.generate');
            Route::post('/{certificateId}/revoke', 'revoke')->name('dashboard.certificates.revoke');
        });

        Route::prefix('financials')->controller(FinancialController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.financials.index');
        });

        Route::prefix('settings')->controller(SettingController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.settings.index');
            Route::post('/profile', 'updateProfile')->name('dashboard.settings.profile');
            Route::post('/paystack', 'updatePaystack')->name('dashboard.settings.paystack');
            Route::post('/notifications', 'updateNotifications')->name('dashboard.settings.notifications');
        });


        Route::prefix('profile')->controller(ProfileController::class)->group(function () {
            Route::get('', 'show')->name('dashboard.profile.show');
            Route::post('', 'update')->name('dashboard.profile.update');
        });

        Route::prefix('verification')->controller(TenantVerificationController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.verification.index');
            Route::post('', 'submit')->name('dashboard.verification.submit');
        });


        Route::prefix('reports')->controller(ReportController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.reports.index');
        });


        Route::prefix('parents')->controller(ParentController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.parents.index');
            Route::get('/{parentId}', 'single');
        });

        Route::prefix('complaints')->controller(ComplaintController::class)->group(function () {
            Route::get('', 'index')->name('dashboard.complaints.index');
        });
    });
});
