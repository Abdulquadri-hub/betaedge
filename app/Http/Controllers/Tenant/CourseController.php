<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function show(Request $request) {
        
        $courseId = $request->course;

        return Inertia::render('School/CourseDetail', [
            'courseId' => $courseId
        ]);
    }
}
