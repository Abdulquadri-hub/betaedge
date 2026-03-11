<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index(Request $request) {
        return Inertia::render('School/Dashboard/Courses/Index');
    }

    public function single(Request $request, $courseId) {
        return Inertia::render('School/Dashboard/Courses/Detail');
    }

    public function create() {
        return Inertia::render('School/Dashboard/Courses/Builder', [
            'course'    => null,
            'materials' => [],
            'batches'   => [],
        ]);
    }

    public function save(Request $request) {
        // return Inertia::render('School/Dashboard/Courses/Builder');
    }

    public function edit(Request $request, $courseId) {
        return Inertia::render('School/Dashboard/Courses/Builder');
    }

    public function publish(Request $request, $courseId) {
        //
    }

    public function archive(Request $request, $courseId) {
        //
    }

    public function duplicate(Request $request, $courseId) {
        //
    }
}
