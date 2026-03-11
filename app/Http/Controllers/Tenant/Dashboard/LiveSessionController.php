<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LiveSessionController extends Controller
{
    public function index() {
        return Inertia::render('School/Dashboard/LiveSessions/Index');
    }
}
