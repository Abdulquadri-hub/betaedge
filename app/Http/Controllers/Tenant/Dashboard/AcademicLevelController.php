<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Contracts\Repositories\School\AcademicLevelRepositoryInterface;

class AcademicLevelController extends Controller
{
    public function __construct(
        protected AcademicLevelRepositoryInterface $academicLevelRepo
    ) {}

    public function list()
    {
        return response()->json([
            'success' => true,
            'data' => $this->academicLevelRepo->getActive(),
        ]);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'is_active']);
        $perPage = $request->get('per_page', 15);

        $levels = $this->academicLevelRepo->getPaginated($perPage, $filters);

        return Inertia::render('School/Dashboard/Settings/AcademicLevels', [
            'levels' => $levels,
            'filters' => $filters,
            'stats' => [
                'total' => $this->academicLevelRepo->count(),
                'active' => $this->academicLevelRepo->count(), // Can be optimized
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:academic_levels,name,NULL,id,tenant_id,' . session('active_tenant_id'),
            'code' => 'required|string|max:20|unique:academic_levels,code,NULL,id,tenant_id,' . session('active_tenant_id'),
            'level_number' => 'nullable|integer|min:0|unique:academic_levels,level_number,NULL,id,tenant_id,' . session('active_tenant_id'),
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        try {
            $level = $this->academicLevelRepo->create($validated);
            return response()->json([
                'success' => true,
                'message' => 'Academic level created successfully',
                'data' => $level,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $levelId)
    {
        $level = $this->academicLevelRepo->getById($levelId);

        if (!$level) {
            return response()->json([
                'success' => false,
                'message' => 'Academic level not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:academic_levels,name,' . $levelId . ',id,tenant_id,' . session('active_tenant_id'),
            'code' => 'required|string|max:20|unique:academic_levels,code,' . $levelId . ',id,tenant_id,' . session('active_tenant_id'),
            'level_number' => 'nullable|integer|min:0|unique:academic_levels,level_number,' . $levelId . ',id,tenant_id,' . session('active_tenant_id'),
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        try {
            $updated = $this->academicLevelRepo->update($levelId, $validated);
            return response()->json([
                'success' => true,
                'message' => 'Academic level updated successfully',
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function destroy(Request $request, $levelId)
    {
        $level = $this->academicLevelRepo->getById($levelId);

        if (!$level) {
            return response()->json([
                'success' => false,
                'message' => 'Academic level not found',
            ], 404);
        }

        if ($this->academicLevelRepo->isInUse($levelId)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete academic level in use by active courses',
            ], 422);
        }

        try {
            $this->academicLevelRepo->delete($levelId);
            return response()->json([
                'success' => true,
                'message' => 'Academic level deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
