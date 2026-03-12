<?php

namespace App\Http\Controllers\Instructors\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobController extends Controller
{
    public function index() {
        return Inertia::render('Instructor/Dashboard/Jobs/Index');
    }

    public function apply($jobId) {
        //
    }
}
