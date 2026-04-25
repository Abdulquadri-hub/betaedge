<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Contracts\Repositories\School\MaterialRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseMaterialController extends Controller
{
    public function __construct(
        protected MaterialRepositoryInterface $materialRepository
    ) {}

    public function store(Request $request, $tenant, $courseId)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'material_type' => 'required|in:pdf,document,link,video',
            'module'        => 'nullable|string|max:100',
            'file'          => 'required_if:material_type,pdf,document|nullable|file|mimes:pdf,doc,docx,pptx,xlsx|max:51200',
            'url'           => 'required_if:material_type,link,video|nullable|url',
        ]);

        $tenantId = (int) session('active_tenant_id');

        $dbType = match ($request->material_type) {
            'pdf'      => 'pdf',
            'document' => 'document',
            'link'     => 'link',
            'video'    => 'video',
            default    => 'document',
        };

        $filePath  = null;
        $fileUrl   = null;
        $mimeType  = null;
        $sizeBytes = null;

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file      = $request->file('file');
            $mimeType  = $file->getMimeType();
            $sizeBytes = $file->getSize();
            $filePath  = $file->store("tenants/{$tenantId}/course-materials/{$courseId}", 'public');
            $fileUrl   = asset('storage/' . $filePath);
        } elseif ($request->filled('url')) {
            $fileUrl = $request->url;
        }

        $count = $this->materialRepository->countForCourse($tenantId, (int) $courseId);

        $this->materialRepository->createForCourse([
            'tenant_id'       => $tenantId,
            'course_id'       => (int) $courseId,
            'title'           => $request->title,
            'description'     => $request->module ?? 'General',
            'material_type'   => $dbType,
            'file_url'        => $fileUrl,
            'file_path'       => $filePath,
            'file_mime_type'  => $mimeType,
            'file_size_bytes' => $sizeBytes,
            'display_order'   => $count + 1,
            'is_published'    => true,
        ]);

        return redirect()->back()->with('success', 'Material added');
    }

    public function destroy($tenant, $courseId, $materialId)
    {
        $tenantId = (int) session('active_tenant_id');

        $material = $this->materialRepository->findForCourse($tenantId, (int) $courseId, (int) $materialId);

        if (! $material) {
            abort(404);
        }

        if ($material->file_path) {
            Storage::disk('public')->delete($material->file_path);
        }

        $this->materialRepository->delete($material);

        return redirect()->back()->with('success', 'Material removed');
    }
}