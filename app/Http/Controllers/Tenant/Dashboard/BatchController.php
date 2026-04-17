<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Contracts\Repositories\School\BatchRepositoryInterface;
use App\Contracts\Repositories\School\CourseRepositoryInterface;

class BatchController extends Controller
{
    public function __construct(
        protected BatchRepositoryInterface $batchRepo,
        protected CourseRepositoryInterface $courseRepo
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

    public function single(Request $request, $batchId) {
        $batch = $this->batchRepo->getById($batchId);

        if (!$batch) {
            return redirect()->route('dashboard.batches.index')->with('error', 'Batch not found');
        }

        return Inertia::render('School/Dashboard/Batches/DetailPage', [
            'batch' => $batch,
            'students' => $batch->activeStudents()->paginate(15),
        ]);
    }

    public function create() {
        $courses = $this->courseRepo->getActive();

        return Inertia::render('School/Dashboard/Batches/Builder', [
            'batch' => null,
            'courses' => $courses,
        ]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'max_students' => 'required|integer|min:1',
            'schedule_day' => 'nullable|string|max:50',
            'session_time' => 'nullable|date_format:H:i',
        ]);

        $validated['tenant_id'] = session('active_tenant_id');
        $batch = $this->batchRepo->create($validated);

        return redirect()->route('dashboard.batches.single', $batch->id)
            ->with('success', 'Batch created successfully');
    }

    public function edit(Request $request, $batchId) {
        $batch = $this->batchRepo->getById($batchId);

        if (!$batch) {
            return redirect()->route('dashboard.batches.index')->with('error', 'Batch not found');
        }

        $courses = $this->courseRepo->getActive();

        return Inertia::render('School/Dashboard/Batches/Builder', [
            'batch' => $batch,
            'courses' => $courses,
        ]);
    }

    public function update(Request $request, $batchId) {
        $batch = $this->batchRepo->getById($batchId);

        if (!$batch) {
            return redirect()->route('dashboard.batches.index')->with('error', 'Batch not found');
        }

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'max_students' => 'required|integer|min:1',
            'schedule_day' => 'nullable|string|max:50',
            'session_time' => 'nullable|date_format:H:i',
        ]);

        $updated = $this->batchRepo->update($batchId, $validated);

        return redirect()->route('dashboard.batches.single', $updated->id)
            ->with('success', 'Batch updated successfully');
    }

    public function delete(Request $request, $batchId) {
        $batch = $this->batchRepo->getById($batchId);

        if (!$batch) {
            return redirect()->route('dashboard.batches.index')->with('error', 'Batch not found');
        }

        if ($batch->activeStudents()->count() > 0) {
            return back()->with('error', 'Cannot delete batch with active students');
        }

        $batch->delete();

        return redirect()->route('dashboard.batches.index')
            ->with('success', 'Batch deleted successfully');
    }
}


