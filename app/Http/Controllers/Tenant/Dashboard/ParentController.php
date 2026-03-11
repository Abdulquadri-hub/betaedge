<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParentController extends Controller
{
    public function index() {
        return Inertia::render('School/Dashboard/Parents/Index');
    }

    public function single($parentId) {
        return Inertia::render('School/Dashboard/Parents/Detail');
    }

    public function message($parentId) {
        //
    }

    public function thresholds($parentId) {
        //
    }
}
