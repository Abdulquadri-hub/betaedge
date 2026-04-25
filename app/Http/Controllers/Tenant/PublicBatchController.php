<?php

namespace App\Http\Controllers\Tenant;

use App\Contracts\Services\School\PublicBatchServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PublicBatchRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicBatchController extends Controller
{
    public function __construct(
        protected PublicBatchServiceInterface $batchService
    ) {}

    public function index(PublicBatchRequest $request, string $tenant)
    {
        return Inertia::render('School/Public/Batches/Index',
            $this->batchService->list($tenant, $request->validated())
        );
    }

    public function show(Request $request, string $tenant, string $batchSlug)
    {
        return Inertia::render('School/Public/Batches/Show',
            $this->batchService->get($tenant, $batchSlug)
        );
    }
}
