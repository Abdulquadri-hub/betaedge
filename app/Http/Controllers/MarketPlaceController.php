<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class MarketPlaceController extends Controller
{
    public function lists() {
        return Inertia::render('Marketplace');
    }
}
