<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Contracts\Repositories\School\CourseRepositoryInterface;
use App\Contracts\Repositories\School\AcademicLevelRepositoryInterface;

class CourseController extends Controller
{
    public function __construct(
        protected CourseRepositoryInterface $courseRepo,
        protected AcademicLevelRepositoryInterface $academicLevelRepo
    ) {}

    public function index(Request $request) {
        $filters = $request->only(['search', 'status', 'academic_level_id']);
        $perPage = $request->get('per_page', 15);

        $courses = $this->courseRepo->getPaginated($perPage, $filters);

        return Inertia::render('School/Dashboard/Courses/Index', [
            'courses' => $courses,
            'filters' => $filters,
            'stats' => $this->courseRepo->getStats(),
        ]);
    }

    public function single(Request $request, $courseId) {
        $course = $this->courseRepo->getById($courseId);

        if (!$course) {
            return redirect('/dashboard/courses')->with('error', 'Course not found');
        }

        return Inertia::render('School/Dashboard/Courses/Detail', [
            'course' => $course,
            'enrollments' => $course->enrollments()->paginate(15),
        ]);
    }

    public function create() {
        return Inertia::render('School/Dashboard/Courses/Builder', [
            'academicLevels' => $this->academicLevelRepo->getActive(),
        ]);
    }

    public function save(Request $request) {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'course_code' => 'required|string|max:50|unique:courses,course_code',
            'description' => 'nullable|string',
            'academic_level_id' => 'required|exists:academic_levels,id',
            'duration_weeks' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'max_students' => 'nullable|integer|min:1',
        ]);

        $course = $this->courseRepo->create($validated);

        return redirect("/dashboard/courses/{$course->id}")->with('success', 'Course created successfully');
    }

    public function edit(Request $request, $courseId) {
        $course = $this->courseRepo->getById($courseId);

        if (!$course) {
            return redirect('/dashboard/courses')->with('error', 'Course not found');
        }

        // Load related materials and batches
        $materials = $course->materials()->get();
        $batches = $course->batches()->get();

        return Inertia::render('School/Dashboard/Courses/Builder', [
            'course' => $course,
            'materials' => $materials,
            'batches' => $batches,
            'academicLevels' => $this->academicLevelRepo->getActive(),
        ]);
    }

    public function update(Request $request, $courseId) {
        $course = $this->courseRepo->getById($courseId);

        if (!$course) {
            return redirect('/dashboard/courses')->with('error', 'Course not found');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_weeks' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'max_students' => 'nullable|integer|min:1',
        ]);

        $updated = $this->courseRepo->update($courseId, $validated);

        return redirect("/dashboard/courses/{$updated->id}")->with('success', 'Course updated successfully');
    }

    public function publish(Request $request, $courseId) {
        $course = $this->courseRepo->getById($courseId);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        $this->courseRepo->update($courseId, [
            'is_published' => true,
            'published_at' => now(),
        ]);

        return response()->json(['message' => 'Course published successfully']);
    }

    public function archive(Request $request, $courseId) {
        $course = $this->courseRepo->getById($courseId);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        $this->courseRepo->update($courseId, [
            'status' => 'archived',
        ]);

        return response()->json(['message' => 'Course archived successfully']);
    }

    public function duplicate(Request $request, $courseId) {
        $course = $this->courseRepo->getById($courseId);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        $duplicated = $this->courseRepo->create([
            'title' => $course->title . ' (Copy)',
            'course_code' => $course->course_code . '_' . uniqid(),
            'description' => $course->description,
            'academic_level_id' => $course->academic_level_id,
            'duration_weeks' => $course->duration_weeks,
            'price' => $course->price,
            'max_students' => $course->max_students,
            'category' => $course->category,
            'level' => $course->level,
        ]);

        return response()->json(['message' => 'Course duplicated successfully', 'course_id' => $duplicated->id]);
    }

    public function destroy(Request $request, $courseId) {
        $course = $this->courseRepo->getById($courseId);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        if ($course->activeBatches()->count() > 0) {
            return response()->json(['error' => 'Cannot delete course with active batches'], 422);
        }

        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
    }
}

