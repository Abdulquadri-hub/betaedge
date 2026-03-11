<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index() {
        return Inertia::render('School/Dashboard/Students/Index');
    }

    public function single(Request $request, $studentId) {
        return Inertia::render('School/Dashboard/Students/Detail');
    }

    public function suspend(Request $request, $studentId) {
        //
    }

    public function activate(Request $request, $studentId) {
        //
    }
}
