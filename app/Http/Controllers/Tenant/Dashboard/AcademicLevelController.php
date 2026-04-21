<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
            'data'    => $this->academicLevelRepo->getActive(),
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = session('active_tenant_id');

        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'code'          => 'required|string|max:20',
            'level_number'  => 'nullable|integer|min:0',
            'description'   => 'nullable|string|max:500',
            'is_active'     => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        try {
            $level = $this->academicLevelRepo->create($validated);
            return redirect()->back()->with('success', 'Academic level created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $tenant, $levelId)
    {
        $id = (int) $levelId;

        $level = $this->academicLevelRepo->getById($id);

        if (!$level) {
            return redirect()->back()->withErrors(['message' => 'Academic level not found' . $id]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
            'level_number' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        try {
            $this->academicLevelRepo->update($id, $validated);
            return redirect()->back()->with('success', 'Academic level updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function toggle($tenant, $levelId)
    {
        $id = (int) $levelId;
        $level = $this->academicLevelRepo->getById($id);

        if (!$level) {
            return redirect()->back()->withErrors(['message' => 'Academic level not found']);
        }

        try {
            $this->academicLevelRepo->update($id, ['is_active' => !$level->is_active]);
            return redirect()->back()->with('success', 'Academic level status updated');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function destroy($tenant, $levelId)
    {
        $id    = (int) $levelId;
        $level = $this->academicLevelRepo->getById($id);

        if (!$level) {
            return redirect()->back()->withErrors(['message' => 'Academic level not found']);
        }

        if ($this->academicLevelRepo->isInUse($id)) {
            return redirect()->back()->withErrors(['message' => 'Cannot delete a level that is in use by active courses']);
        }

        try {
            $this->academicLevelRepo->delete($id);
            return redirect()->back()->with('success', 'Academic level deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }
}
