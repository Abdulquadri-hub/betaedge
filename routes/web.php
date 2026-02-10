<?php

use Inertia\Inertia;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\MarketPlaceController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Tenant\PublicPageController;
use App\Http\Controllers\Auth\SelectSchoolController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\Tenant\CourseController;
use App\Http\Controllers\Tenant\EnrollmentController;

/**
 * Main Domain (platform) Routes
 */
Route::domain(config('app.main_domain'))->middleware(['web'])->group(function () {

    /**
     * Homepage Routes
     */
    Route::controller(PlatformController::class)->group(function () {
        Route::get('/', 'landing')->name('home');
    });

    /**
     * Marketplace Routes
     */
    Route::controller(MarketPlaceController::class)->group(function () {
        Route::get('/marketplace', 'lists');
    });

    /**
     * Authentication Routes
     */
    Route::controller(LoginController::class)->group(function () {
        Route::get('/auth/login', 'index')->name('login.index');
        Route::post('auth/login', 'initiate')->name('login.initiate');
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
});


/**
 * Tenant / Schools Routes
 */

Route::domain('{tenant}.' . config('app.main_domain'))->middleware(['web', 'tenant'])->group(function () {
    /**
     * School Page Route
    */
    Route::controller(PublicPageController::class)->group(function () {
        Route::get('/',  'landing')->name('tenant.landing');

        /**
         * Course
        */
        Route::controller(CourseController::class)->group(function () {
            Route::get('/course/{course}', 'show')->name('tenant.course');
        });

        /**
         * Enrollment
         */
        Route::controller(EnrollmentController::class)->group(function () {
            Route::get('/enroll', 'showEnroll')->name('tenant.enroll');
        });
        
    });
});
