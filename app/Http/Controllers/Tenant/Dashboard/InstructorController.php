<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstructorController extends Controller
{
    public function index() {
        return Inertia::render('School/Dashboard/Instructors/Index');
    }

    public function single(Request $request, $instructorId) {
        return Inertia::render('School/Dashboard/Instructors/Detail');
    }

    public function invite(Request $request) {
        return Inertia::render('School/Dashboard/Instructors/Invite');
    }

    public function update(Request $request, $instructorId) {
        return Inertia::render('School/Dashboard/Instructors/edit');
    }

    public function markPaid(Request $request, $instructorId) {
        //
    }

    public function destory(Request $request, $instructorId) {
        //
    }

}
