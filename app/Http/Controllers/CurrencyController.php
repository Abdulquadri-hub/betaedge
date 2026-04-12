<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Detect user's currency from IP address
     */
    public function detect(Request $request)
    {
        $ip = $request->input('ip') ?? $request->ip();
        
        $currencyData = CurrencyService::detectCurrencyFromIP($ip);

        return response()->json($currencyData);
    }
}
