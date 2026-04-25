<?php

namespace App\Http\Controllers\Tenant;

use App\Contracts\Services\School\PublicPageServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicPageController extends Controller
{
    public function __construct(
        protected PublicPageServiceInterface $pageService
    ) {}

    public function landing(Request $request, string $tenant)
    {
        return Inertia::render(
            'School/Public',
            $this->pageService->landing($tenant)
        );
    }
}
