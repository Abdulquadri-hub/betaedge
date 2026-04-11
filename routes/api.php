<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\CurrencyController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['throttle:60,1'])->group(function () {
    Route::controller(OnboardingController::class)->group(function () {
        Route::post('/onboarding/validate-slug', 'validateSlug')->name('api.onboarding.validate-slug');
        Route::post('/onboarding/upload-logo', 'uploadLogo')->name('api.onboarding.upload-logo');
    });

    // Currency detection - no auth required
    Route::controller(CurrencyController::class)->group(function () {
        Route::get('/currency/detect', 'detect')->name('api.currency.detect');
    });
});
