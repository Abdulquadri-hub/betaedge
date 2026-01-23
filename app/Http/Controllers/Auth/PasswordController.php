<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PasswordController extends Controller
{
    public function showForgot() {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function forgot() {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function showReset() {
        return Inertia::render('Auth/ResetPassword');
    }

    public function reset() {
        return Inertia::render('Auth/ResetPassword');
    }
}
