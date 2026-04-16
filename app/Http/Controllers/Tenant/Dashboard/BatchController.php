<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Contracts\Repositories\School\BatchRepositoryInterface;

class BatchController extends Controller
{
    public function __construct(
        protected BatchRepositoryInterface $batchRepo
    ) {}

    public function index(Request $request) {
        $filters = $request->only(['search', 'status', 'course_id']);
        $perPage = $request->get('per_page', 15);

        $batches = $this->batchRepo->getPaginated($perPage, $filters);

        return Inertia::render('School/Dashboard/Batches/Index', [
            'batches' => $batches,
            'filters' => $filters,
            'stats' => $this->batchRepo->getStats(),
        ]);
    }

    public function single(Request $request) {
        $batchId = $request->route('batchId');
        $batch = $this->batchRepo->getById($batchId);

        if (!$batch) {
            return redirect()->route('dashboard.batches.index')->with('error', 'Batch not found');
        }

        return Inertia::render('School/Dashboard/Batches/DetailPage', [
            'batch' => $batch,
            'students' => $batch->activeStudents()->paginate(15),
        ]);
    }
}

