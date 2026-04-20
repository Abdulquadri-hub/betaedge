<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseMaterialController extends Controller
{
    public function store(Request $request, $tenant, $courseId)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'type' => 'required|in:pdf,document,link,video',
            'module'        => 'nullable|string|max:100',
            'file'          => 'required_if:material_type,pdf,document|nullable|file|mimes:pdf,doc,docx,pptx,xlsx|max:51200',
            'url'           => 'required_if:material_type,link,video|nullable|url',
        ]);

        $tenantId  = session('active_tenant_id');
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
        } elseif ($request->url) {
            $fileUrl = $request->url;
        }

        $count = Material::where('tenant_id', $tenantId)
            ->where('course_id', (int) $courseId)
            ->count();

        Material::create([
            'tenant_id'       => $tenantId,
            'course_id'       => (int) $courseId,
            'title'           => $request->title,
            'description'     => $request->module ?? 'General',
            'material_type'   => $request->type,
            'file_url'        => $fileUrl,
            'file_path'       => $filePath,
            'file_mime_type'  => $mimeType,
            'file_size_bytes' => $sizeBytes,
            'display_order'   => $count + 1,
            'is_published'    => true,
        ]);

        return redirect()->back()->with('success', 'Material added');
    }

    public function destroy($courseId, $tenant, $materialId)
    {
        $tenantId = session('active_tenant_id');
        $material = Material::where('tenant_id', $tenantId)
            ->where('course_id', (int) $courseId)
            ->findOrFail((int) $materialId);

        if ($material->file_path) {
            Storage::disk('public')->delete($material->file_path);
        }

        $material->delete();

        return redirect()->back()->with('success', 'Material removed');
    }
}