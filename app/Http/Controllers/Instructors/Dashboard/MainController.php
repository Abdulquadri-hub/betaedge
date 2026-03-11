<?php

namespace App\Http\Controllers\Instructors\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MainController extends Controller
{
    public function index() {
        return Inertia::render('Instructor/Dashboard/Home');
    }

    public function switchSchool($tenantId) {
        return Inertia::render('');
    }
}
