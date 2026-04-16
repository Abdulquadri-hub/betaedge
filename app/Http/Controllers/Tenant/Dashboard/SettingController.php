<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        $tenant = app('tenant');

        return Inertia::render('School/Dashboard/Settings/Index', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request): Response
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'website' => 'nullable|url',
            'address' => 'nullable|string|max:255',
            'timezone' => 'nullable|timezone',
            'primary_color' => 'nullable|regex:/^#[0-9A-F]{6}$/i',
            'secondary_color' => 'nullable|regex:/^#[0-9A-F]{6}$/i',
        ]);

        $tenant->update($validated);

        return Inertia::render('School/Dashboard/Settings/Index', [
            'tenant' => $tenant,
            'success' => 'School settings updated successfully.',
        ]);
    }
}
