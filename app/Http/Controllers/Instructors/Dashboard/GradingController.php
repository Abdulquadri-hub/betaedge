<?php

namespace App\Http\Controllers\Instructors\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GradingController extends Controller
{
    public function index() {
        return Inertia::render('Instructor/Dashboard/Gradings/Index');
    }

    public function grade($submissionId) {
        //
    }
}
