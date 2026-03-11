<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BatchController extends Controller
{
    public function index(Request $request) {
        return Inertia::render('School/Dashboard/Batches/Index');
    }

    public function single() {
        return Inertia::render('School/Dashboard/Batches/DetailPage');
    }
}
