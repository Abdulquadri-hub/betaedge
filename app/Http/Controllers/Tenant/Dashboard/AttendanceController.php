<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Attendance is tracked per ClassSession per Student.
 * The Attendance model uses class_session_id as the FK.
 *
 * Flow:
 *   Instructor opens session → clicks "Mark Attendance"
 *   → sees list of enrolled students
 *   → marks each as present/absent/late
 */
class AttendanceController extends Controller
{
    /**
     * Show attendance sheet for a specific class session.
     */
    public function show(Request $request, $sessionId)
    {
        $tenantId = (int) session('active_tenant_id');

        $session = ClassSession::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with(['batch', 'course', 'instructor.user'])
            ->findOrFail($sessionId);

        // Get all active students enrolled in this batch
        $enrolledStudents = $session->batch
            ? $session->batch->activeStudents()->with('user')->get()
            : collect();

        // Get existing attendance records for this session
        $existing = Attendance::withoutGlobalScopes()
            ->where('class_session_id', $sessionId)
            ->get()
            ->keyBy('student_id');

        $roster = $enrolledStudents->map(fn ($student) => [
            'student_id'       => $student->id,
            'name'             => $student->user?->full_name ?? '—',
            'student_code'     => $student->student_id,
            'status'           => $existing->get($student->id)?->status ?? 'absent',
            'joined_at'        => $existing->get($student->id)?->joined_at?->format('H:i'),
            'left_at'          => $existing->get($student->id)?->left_at?->format('H:i'),
            'duration_minutes' => $existing->get($student->id)?->duration_minutes,
            'attendance_id'    => $existing->get($student->id)?->id,
        ]);

        $stats = [
            'total'   => $roster->count(),
            'present' => $roster->where('status', 'present')->count(),
            'absent'  => $roster->where('status', 'absent')->count(),
            'late'    => $roster->where('status', 'late')->count(),
            'rate'    => $roster->count() > 0
                ? round($roster->whereIn('status', ['present', 'late'])->count() / $roster->count() * 100, 1)
                : 0,
        ];

        return Inertia::render('School/Dashboard/Attendance/Sheet', [
            'session' => [
                'id'            => $session->id,
                'title'         => $session->title,
                'status'        => $session->ui_status,
                'batch_name'    => $session->batch?->batch_name,
                'course_name'   => $session->course?->title,
                'date'          => $session->scheduled_start?->format('F j, Y'),
                'time'          => $session->scheduled_start?->format('H:i'),
            ],
            'roster' => $roster,
            'stats'  => $stats,
        ]);
    }

    /**
     * Save attendance for a session.
     * Accepts an array of { student_id, status, joined_at?, left_at? }
     */
    public function save(Request $request, $sessionId)
    {
        $tenantId = (int) session('active_tenant_id');

        $session = ClassSession::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($sessionId);

        $request->validate([
            'records'                => 'required|array',
            'records.*.student_id'   => 'required|exists:students,id',
            'records.*.status'       => 'required|in:present,absent,late,excused',
            'records.*.joined_at'    => 'nullable|string',
            'records.*.left_at'      => 'nullable|string',
        ]);

        foreach ($request->records as $record) {
            $studentId = (int) $record['student_id'];

            Attendance::withoutGlobalScopes()->updateOrCreate(
                [
                    'class_session_id' => $session->id,
                    'student_id'       => $studentId,
                ],
                [
                    'tenant_id'   => $tenantId,
                    'status'      => $record['status'],
                    'instructor_id'=> $session->instructor_id,
                    'joined_at'   => isset($record['joined_at'])
                        ? $session->scheduled_start?->toDateString() . ' ' . $record['joined_at']
                        : null,
                    'left_at'     => isset($record['left_at'])
                        ? $session->scheduled_start?->toDateString() . ' ' . $record['left_at']
                        : null,
                ]
            );
        }

        // Update total_enrolled snapshot on the session
        $presentCount = Attendance::withoutGlobalScopes()
            ->where('class_session_id', $session->id)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $session->update(['actual_participants' => $presentCount]);

        return redirect()->back()->with('success', 'Attendance saved');
    }

    /**
     * Batch attendance report — all sessions for a batch.
     */
    public function batchReport(Request $request, $batchId)
    {
        $tenantId = (int) session('active_tenant_id');

        $sessions = ClassSession::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('batch_id', $batchId)
            ->where('status', 'completed')
            ->with(['course'])
            ->orderBy('scheduled_start')
            ->get();

        $students = \App\Models\Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($batchId)
            ->activeStudents()
            ->with('user')
            ->get();

        $sessionIds = $sessions->pluck('id');

        $allAttendance = Attendance::withoutGlobalScopes()
            ->whereIn('class_session_id', $sessionIds)
            ->get()
            ->groupBy('student_id');

        $report = $students->map(function ($student) use ($sessions, $allAttendance) {
            $studentAttendance = $allAttendance->get($student->id, collect());
            $present = $studentAttendance->whereIn('status', ['present', 'late'])->count();
            $total   = $sessions->count();
            $rate    = $total > 0 ? round($present / $total * 100, 1) : 0;

            return [
                'student_id'  => $student->id,
                'name'        => $student->user?->full_name,
                'student_code'=> $student->student_id,
                'present'     => $present,
                'absent'      => $total - $present,
                'total'       => $total,
                'rate'        => $rate,
                'sessions'    => $sessions->map(fn ($s) => [
                    'session_id' => $s->id,
                    'date'       => $s->scheduled_start?->format('M j'),
                    'status'     => $studentAttendance
                        ->firstWhere('class_session_id', $s->id)?->status ?? 'absent',
                ]),
            ];
        });

        return Inertia::render('School/Dashboard/Attendance/BatchReport', [
            'batch_id' => $batchId,
            'sessions' => $sessions->map(fn ($s) => [
                'id'    => $s->id,
                'title' => $s->title,
                'date'  => $s->scheduled_start?->format('M j, Y'),
            ]),
            'report' => $report,
        ]);
    }
}