<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OnbaordingController;
use App\Http\Controllers\TenantPublicPageController;
use App\Http\Controllers\EmailVerificationController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');


Route::middleware(['guest', 'throttle:60,1'])->group(function () {
    Route::get('/onboarding', [OnbaordingController::class, 'index'])
        ->name('onboarding.index');
    
    Route::post('/onboarding/draft', [OnbaordingController::class, 'saveDraft'])
        ->name('onboarding.draft');
    
    Route::post('/onboarding/submit', [OnbaordingController::class, 'submit'])
        ->middleware('throttle:onboarding')
        ->name('onboarding.submit');
    
    Route::get('/onboarding/status/{jobId}', [OnbaordingController::class, 'status'])
        ->middleware('throttle:onboarding-status')
        ->name('onboarding.status');
});

// Email verification routes
Route::get('/verify-email-notice', [EmailVerificationController::class, 'notice'])
    ->name('verification.notice');

Route::get('/verify-email/{token}', [EmailVerificationController::class, 'verify'])
    ->name('verification.verify');

Route::post('/set-password', [EmailVerificationController::class, 'setPassword'])
    ->name('password.set');

Route::post('/resend-verification', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle:3,60')
    ->name('verification.resend');

// Tenant public pages (with tenant middleware)
Route::domain('{tenant}.' . config('app.main_domain'))
    ->middleware(['web', 'tenant'])
    ->group(function () {
        Route::get('/', [TenantPublicPageController::class, 'landing'])
            ->name('tenant.landing');
        
        Route::get('/about', [TenantPublicPageController::class, 'about'])
            ->name('tenant.about');
        
        Route::get('/register/student', [TenantPublicPageController::class, 'registerStudent'])
            ->name('tenant.register.student');
        
        Route::post('/register/student', [TenantPublicPageController::class, 'storeStudent'])
            ->name('tenant.register.student.store');
        
        Route::get('/register/parent', [TenantPublicPageController::class, 'registerParent'])
            ->name('tenant.register.parent');
        
        Route::post('/register/parent', [TenantPublicPageController::class, 'storeParent'])
            ->name('tenant.register.parent.store');
});
