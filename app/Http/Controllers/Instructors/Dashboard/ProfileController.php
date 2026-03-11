<?php

namespace App\Http\Controllers\Instructors\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit() {
        return Inertia::render('Instructor/Dashboard/Profile/Index');
    }

    public function update() {
        //
    }
}
