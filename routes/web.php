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
use App\Http\Controllers\Instructors\Dashboard\ProfileController;
use App\Http\Controllers\Instructors\Dashboard\SessionController as InstructorSessionController;
use App\Http\Controllers\Instructors\Dashboard\StudentController as InstructorStudentController;
use App\Http\Controllers\Instructors\Dashboard\VerificationController;
use App\Http\Controllers\Instructors\OnboardingController as InstructorsOnboardingController;
use App\Http\Controllers\MarketPlaceController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\Tenant\CourseController as TenantController;
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
use App\Http\Controllers\Tenant\Dashboard\ReportController;
use App\Http\Controllers\Tenant\Dashboard\SettingController;
use App\Http\Controllers\Tenant\Dashboard\StudentController;
use App\Http\Controllers\Tenant\Dashboard\VerificationController as TenantVerificationController;
use App\Http\Controllers\Tenant\EnrollmentController as PublicEnrollmentController;
use App\Http\Controllers\Tenant\PublicPageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::domain(config('app.main_domain'))->middleware(['web'])->group(function () {

    Route::controller(PlatformController::class)->group(function () {
        Route::get('/', 'landing')->name('home');
    });

    Route::controller(MarketPlaceController::class)->group(function () {
        Route::get('/marketplace', 'lists');
    });

    Route::controller(LoginController::class)->group(function () {
        Route::get('/auth/login', 'index')->name('login.index');
        Route::post('auth/login', 'initiate')->name('login.initiate');
    });

    Route::controller(LogoutController::class)->group(function () {
        Route::post('/auth/logout', 'logout')->name('logout')->middleware('auth');
    });

    Route::controller(PasswordController::class)->group(function () {
        Route::get('/auth/forgot-password', 'showforgot');
        Route::post('/auth/forgot-password', 'forgot');
        Route::get('/auth/reset-password', 'showReset');
        Route::post('/auth/reset-password', 'reset');
    });

    Route::controller(SelectSchoolController::class)->group(function () {
        Route::get('/auth/select-school', 'showSelectSchool');
        Route::post('/auth/select-school', 'selectSchool');
    });

    Route::controller(EmailVerificationController::class)->group(function () {
        Route::get('/verification/notice',  'notice')->name('verification.notice');
        Route::get('/verification/verify/{token}',  'verify')->name('verification.verify');
        Route::post('/verification/set-password',  'setPassword')->name('password.set');
        Route::post('/verification/resend',  'resend')->middleware('throttle:3,60')->name('verification.resend');
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

    Route::prefix('instructor')
        //->middleware(['auth', 'verified', 'instructor'])
        ->group(function () {
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

            Route::prefix('profile')->controller(ProfileController::class)->group(function () {
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
        Route::get('/',  'landing')->name('tenant.landing');
    });

    Route::controller(TenantController::class)->group(function () {
        Route::get('/course/{course}', 'show')->name('tenant.course');
    });

    Route::controller(PublicEnrollmentController::class)->group(function () {
        Route::get('/enroll', 'showEnroll')->name('tenant.enroll');
    });


    Route::prefix('dashboard')->middleware(['auth', 'verified', 'tenant.access'])->group(function () {
        Route::controller(HomeController::class)->group(function () {
            Route::get('', 'index');
        });

        Route::prefix('batches')->controller(BatchController::class)->group(function () {
            Route::get('', 'index');
            Route::get('/{batchId}', 'single');
        });

        Route::prefix('courses')->controller(CourseController::class)->group(function () {
            Route::get('', 'index');
            Route::get('/{courseId}', 'single');
            Route::get('/create', 'create');
            Route::post('/create', 'save');
            Route::get('/{courseId}/edit', 'edit');
            Route::post('/{courseId}/edit', 'update');
            Route::post('/{courseId}/publish', 'publish')->name('courses.publish');
            Route::post('/{courseId}/archive', 'archive')->name('courses.archive');
            Route::post('/{courseId}/duplicate', 'duplicate')->name('courses.duplicate');
        });

        Route::prefix('verification')->controller(TenantVerificationController::class)->group(function () {
            Route::get('', 'index');
        });

        Route::prefix('courses')->controller(CourseMaterialController::class)->group(function () {
            Route::post('/{courseId}/materials', 'store')->name('courses.materials.store');
            Route::delete('/{courseId}/materials/{material}', 'destroy')->name('courses.materials.destroy');
        });

        Route::prefix('students')->controller(StudentController::class)->group(function () {
            Route::get('', 'index')->name('students.index');
            Route::get('/{student}', 'single')->name('students.single');
            Route::post('/{student}/suspend', 'suspend')->name('students.suspend');
            Route::post('/{student}/activate', 'activate')->name('students.activate');
        });

        Route::prefix('instructors')->controller(InstructorController::class)->group(function () {
            Route::get('', 'index')->name('instructors.index');
            Route::get('/{instructorId}',   'single')->name('instructors.single');
            Route::post('/invite', 'invite')->name('instructors.invite');
            Route::post('/{instructorId}', 'update')->name('instructors.update');
            Route::delete('/{instructorId}', 'destroy')->name('instructors.destroy');
            Route::post('/{instructorId}/mark-paid',  'markPaid')->name('instructors.markPaid');
        });

        Route::prefix('enrollments')->controller(EnrollmentController::class)->group(function () {
            Route::get('', 'index')->name('enrollments.index');
            Route::patch('/{id}/approve', 'approve')->name('enrollments.approve');
            Route::patch('/{id}/reject', 'reject')->name('enrollments.reject');
        });

        Route::prefix('live-sessions')->controller(LiveSessionController::class)->group(function () {
            Route::get('', 'index');
        });

        Route::prefix('certificates')->controller(CertificateController::class)->group(function () {
            Route::get('', 'index');
        });

        Route::prefix('complaints')->controller(ComplaintController::class)->group(function () {
            Route::get('', 'index');
        });

        Route::prefix('settings')->controller(SettingController::class)->group(function () {
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


    });
});
