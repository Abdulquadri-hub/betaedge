<?php

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\School\LiveSessionRepositoryInterface;
use App\Models\Batch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LiveSessionController extends Controller
{
    public function __construct(
        protected LiveSessionRepositoryInterface $sessionRepo,
    ) {}

    public function index(Request $request)
    {
        $tenantId  = (int) session('active_tenant_id');
        $filters   = $request->only(['search', 'status', 'batch_id', 'course_id']);
        $paginated = $this->sessionRepo->getPaginated(20, $filters);
        $sessions  = $paginated->getCollection()->map(fn ($s) => $this->formatSession($s));

        // Batches with their courses — for the create dialog selectors
        $batches = Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['planning', 'active'])
            ->with(['batchCourses.course'])
            ->orderBy('batch_name')
            ->get()
            ->map(fn ($b) => [
                'id'      => $b->id,
                'name'    => $b->batch_name,
                'courses' => $b->batchCourses->map(fn ($bc) => [
                    'id'             => $bc->course_id,
                    'title'          => $bc->course?->title ?? '—',
                    'instructor_id'  => $bc->instructor_id,
                    'session_day'    => $bc->session_day,
                    'session_time'   => $bc->session_time,
                    'duration_minutes' => $bc->session_duration_minutes,
                    'platform'       => $bc->session_platform,
                ]),
            ]);

        $liveNow = $this->sessionRepo->getLiveNow();

        return Inertia::render('School/Dashboard/LiveSessions/Index', [
            'sessions'   => $sessions,
            'liveNow'    => $liveNow ? $this->formatSession($liveNow) : null,
            'stats'      => $this->sessionRepo->getStats(),
            'upcoming'   => collect($this->sessionRepo->getUpcoming(3))
                ->map(fn ($s) => $this->formatSession((object) $s)),
            'filters'    => $filters,
            'batches'    => $batches,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_id'         => 'required|exists:batches,id',
            'course_id'        => 'required|exists:courses,id',
            'title'            => 'required|string|max:255',
            'scheduled_date'   => 'required|date',
            'scheduled_time'   => 'required|string|max:5',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'meet_link'        => 'nullable|url',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $validated['duration_minutes'] = $validated['duration_minutes'] ?? 90;

        $this->sessionRepo->create($validated);

        return redirect()->back()->with('success', 'Session scheduled');
    }

    public function update(Request $request, $tenant, $sessionId)
    {
        $session = $this->sessionRepo->getById((int) $sessionId);
        if (!$session) {
            return redirect()->back()->withErrors(['message' => 'Session not found']);
        }

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'scheduled_date'   => 'required|date',
            'scheduled_time'   => 'required|string|max:5',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'meet_link'        => 'nullable|url',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $this->sessionRepo->update((int) $sessionId, $validated);

        return redirect()->back()->with('success', 'Session updated');
    }

    public function goLive(Request $request, $tenant, $sessionId)
    {
        $session = $this->sessionRepo->getById((int) $sessionId);
        if (!$session) {
            return redirect()->back()->withErrors(['message' => 'Session not found']);
        }

        $this->sessionRepo->markLive((int) $sessionId);

        return redirect()->back()->with('success', 'Session is now live');
    }

    public function endSession(Request $request, $tenant, $sessionId)
    {
        $session = $this->sessionRepo->getById((int) $sessionId);
        if (!$session) {
            return redirect()->back()->withErrors(['message' => 'Session not found']);
        }

        $request->validate(['total_attendees' => 'nullable|integer|min:0']);
        $this->sessionRepo->markCompleted((int) $sessionId, (int) $request->input('total_attendees', 0));

        return redirect()->back()->with('success', 'Session ended and attendance recorded');
    }

    public function cancel(Request $request, $tenant, $sessionId)
    {
        $session = $this->sessionRepo->getById((int) $sessionId);
        if (!$session) {
            return redirect()->back()->withErrors(['message' => 'Session not found']);
        }

        $this->sessionRepo->markCancelled((int) $sessionId);

        return redirect()->back()->with('success', 'Session cancelled');
    }

    public function destroy($tenant, $sessionId)
    {
        $session = $this->sessionRepo->getById((int) $sessionId);
        if (!$session) {
            return redirect()->back()->withErrors(['message' => 'Session not found']);
        }

        $this->sessionRepo->delete((int) $sessionId);

        return redirect()->back()->with('success', 'Session deleted');
    }

    private function formatSession($session): array
    {
        $get = fn ($key) => is_array($session)
            ? ($session[$key] ?? null)
            : ($session->{$key} ?? null);

        $scheduledStart = $get('scheduled_start');
        if ($scheduledStart && is_string($scheduledStart)) {
            $scheduledStart = \Carbon\Carbon::parse($scheduledStart);
        }

        $dbStatus       = $get('status') ?? 'scheduled';
        $uiStatus       = $dbStatus === 'in_progress' ? 'live' : $dbStatus;
        $totalEnrolled  = (int) ($get('total_enrolled')      ?? 0);
        $totalAttendees = (int) ($get('actual_participants')  ?? 0);

        return [
            'id'               => $get('id'),
            'title'            => $get('title'),
            'batch_id'         => $get('batch_id'),
            'batch_name'       => $get('batch')?->batch_name ?? null,
            'course_id'        => $get('course_id'),
            'course_name'      => $get('course')?->title ?? null,
            'instructor_name'  => $get('instructor')?->user?->full_name ?? null,
            'scheduled_date'   => $scheduledStart?->toDateString(),
            'scheduled_time'   => $scheduledStart?->format('H:i'),
            'duration_minutes' => (int) ($get('duration_minutes') ?? 90),
            'meet_link'        => $get('meeting_url'),
            'status'           => $uiStatus,
            'total_enrolled'   => $totalEnrolled,
            'total_attendees'  => $totalAttendees,
            'attendance_rate'  => $totalEnrolled
                ? round(($totalAttendees / $totalEnrolled) * 100, 1)
                : null,
            'notes'            => $get('notes'),
            'recording_url'    => $get('recording_url'),
            'started_at'       => $get('actual_start'),
            'ended_at'         => $get('actual_end'),
        ];
    }
}