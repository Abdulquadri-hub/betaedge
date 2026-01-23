<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SelectSchoolController extends Controller
{
    public function showSelectSchool() {
        return Inertia::render('Auth/SchoolSelector');
    }

    public function selectSchool() {
        return Inertia::render('Auth/SchoolSelector');
    }
}
