<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnrollmentController extends Controller
{
    public function showEnroll(Request $request) {

        return Inertia::render('School/Enrollment', [
            'query' => fn() => $request->query()
        ]);
    }
}
