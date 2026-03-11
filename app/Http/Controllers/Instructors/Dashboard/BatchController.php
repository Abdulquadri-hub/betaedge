<?php

namespace App\Http\Controllers\Instructors\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BatchController extends Controller
{
    public function index() {
        return Inertia::render('Instructor/Dashboard/Batches/Index');
    }

    public function single($batchId) {
        return inertia::render('Instructor/Dashboard/Batches/Detail');
    }
}
