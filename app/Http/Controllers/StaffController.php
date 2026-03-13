<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\StudentCall;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\School;
use App\Models\ClassSection;
use App\Services\TelecallerScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Carbon;

class StaffController extends Controller
{
    private const LEAD_STATUSES = ['lead', 'interested', 'not_interested', 'walkin_done', 'admission_done', 'follow_up_later'];

    public function show(Request $request, User $staff)
    {
        abort_if($staff->isAdmin(), 404);

        $now = now();
        $today = $now->copy();

        $scoreService = new TelecallerScoreService();
        $dailyTarget = 25;
        $scoreToday = $scoreService->compute($staff->id, $today, $today, $dailyTarget);
        $scoreOverall = $scoreService->computeOverallAverage($staff->id, $dailyTarget);

        $callsQuery = StudentCall::with(['student.classSection.school'])
            ->where('user_id', $staff->id)
            ->orderByDesc('called_at');

        $recentCallsQuery = (clone $callsQuery);

        $callsTotal = (clone $callsQuery)->count();
        $callsConnected = (clone $callsQuery)->where('call_status', StudentCall::STATUS_CONNECTED)->count();
        $notConnectedStatuses = [
            StudentCall::STATUS_NO_ANSWER,
            StudentCall::STATUS_BUSY,
            StudentCall::STATUS_SWITCHED_OFF,
            StudentCall::STATUS_NOT_REACHABLE,
            StudentCall::STATUS_WRONG_NUMBER,
            StudentCall::STATUS_CALLBACK,
        ];
        $callsNotConnected = (clone $callsQuery)->whereIn('call_status', $notConnectedStatuses)->count();

        $filterFrom = $request->has('from_date') ? Carbon::parse($request->input('from_date'))->startOfDay() : null;
        $filterTo = $request->has('to_date') ? Carbon::parse($request->input('to_date'))->endOfDay() : null;
        $filterLeadStatus = $request->filled('lead_status') && in_array($request->input('lead_status'), self::LEAD_STATUSES, true)
            ? $request->input('lead_status')
            : null;
        $filterSchoolId = $request->input('school_id');
        $filterClassSectionId = $request->input('class_section_id');

        if ($filterFrom && $filterTo && $filterFrom->lte($filterTo)) {
            $recentCallsQuery->whereBetween('called_at', [$filterFrom, $filterTo]);
        }
        $recentCalls = $recentCallsQuery->paginate(10, ['*'], 'calls_page')->withQueryString();

        $callsSummaryFiltered = null;
        $dailyStats = [];

        $rangeStart = $filterFrom ?? now()->subDays(29)->startOfDay();
        $rangeEnd = $filterTo ?? now()->endOfDay();
        if ($rangeStart->gt($rangeEnd)) {
            $rangeEnd = $rangeStart->copy()->endOfDay();
        }

        $callsInRange = StudentCall::where('user_id', $staff->id)
            ->whereBetween('called_at', [$rangeStart, $rangeEnd])
            ->get();

        if ($filterFrom && $filterTo && $filterFrom->lte($filterTo)) {
            $callsSummaryFiltered = [
                'total' => $callsInRange->count(),
                'connected' => $callsInRange->where('call_status', StudentCall::STATUS_CONNECTED)->count(),
                'not_connected' => $callsInRange->whereIn('call_status', $notConnectedStatuses)->count(),
            ];
        }

        $byDay = $callsInRange->groupBy(fn ($c) => Carbon::parse($c->called_at)->toDateString());
        $dates = $byDay->keys()->sort()->values();
        foreach ($dates as $dateStr) {
            $dayCalls = $byDay[$dateStr];
            $dailyStats[] = [
                'date' => $dateStr,
                'total' => $dayCalls->count(),
                'connected' => $dayCalls->where('call_status', StudentCall::STATUS_CONNECTED)->count(),
                'not_connected' => $dayCalls->whereIn('call_status', $notConnectedStatuses)->count(),
            ];
        }
        usort($dailyStats, fn ($a, $b) => strcmp($b['date'], $a['date']));

        $studentsQuery = Student::with(['classSection.school'])
            ->where('assigned_to', $staff->id)
            ->orderBy('name');
        if ($filterLeadStatus !== null) {
            $studentsQuery->where('lead_status', $filterLeadStatus);
        }
        if ($filterSchoolId) {
            $studentsQuery->whereHas('classSection', function ($q) use ($filterSchoolId) {
                $q->where('school_id', $filterSchoolId);
            });
        }
        if ($filterClassSectionId) {
            $studentsQuery->where('class_section_id', $filterClassSectionId);
        }

        $allAssignedIds = (clone $studentsQuery)->pluck('id');
        $callCountsByStudent = StudentCall::where('user_id', $staff->id)
            ->whereIn('student_id', $allAssignedIds)
            ->selectRaw('student_id, count(*) as cnt')
            ->groupBy('student_id')
            ->pluck('cnt', 'student_id')
            ->all();

        $totalUncalled = $allAssignedIds->filter(fn ($id) => empty($callCountsByStudent[$id] ?? 0))->count();

        $students = $studentsQuery->paginate(10, ['*'], 'students_page')->withQueryString();

        $campaigns = Campaign::with(['school', 'template'])
            ->where('shot_by', $staff->id)
            ->orderByDesc('created_at')
            ->paginate(5, ['*'], 'campaigns_page')
            ->withQueryString();

        $messagesSent = CampaignRecipient::where('status', 'sent')
            ->whereHas('campaign', fn ($q) => $q->where('shot_by', $staff->id))
            ->count();

        $leadStatusOptions = [
            'lead' => __('Uncalled'),
            'interested' => __('Interested'),
            'not_interested' => __('Not Interested'),
            'walkin_done' => __('Walk-in Done'),
            'admission_done' => __('Admission Done'),
            'follow_up_later' => __('Follow-up Later'),
        ];

        $schools = School::orderBy('name')->get();
        $classSections = collect();
        if ($filterSchoolId) {
            $classSections = ClassSection::where('school_id', $filterSchoolId)
                ->orderBy('class_name')
                ->orderBy('section_name')
                ->get();
        }

        return view('admin.staff.show', [
            'staff' => $staff,
            'scoreToday' => $scoreToday,
            'scoreOverall' => $scoreOverall,
            'callsSummary' => [
                'total' => $callsTotal,
                'connected' => $callsConnected,
                'not_connected' => $callsNotConnected,
            ],
            'callsSummaryFiltered' => $callsSummaryFiltered,
            'dailyStats' => $dailyStats,
            'recentCalls' => $recentCalls,
            'students' => $students,
            'callCountsByStudent' => $callCountsByStudent,
            'totalUncalled' => $totalUncalled,
            'campaigns' => $campaigns,
            'messagesSent' => $messagesSent,
            'filterFrom' => $filterFrom?->toDateString(),
            'filterTo' => $filterTo?->toDateString(),
            'filterLeadStatus' => $filterLeadStatus,
            'leadStatusOptions' => $leadStatusOptions,
            'schools' => $schools,
            'classSections' => $classSections,
        ]);
    }

    public function index()
    {
        $staff = User::where('is_admin', false)
            ->with('createdBy')
            ->orderBy('name')
            ->get();

        return view('admin.staff.index', compact('staff'));
    }

    public function create()
    {
        return view('admin.staff.create');
    }

    public function edit(User $staff)
    {
        abort_if($staff->isAdmin(), 404);

        return view('admin.staff.edit', compact('staff'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'can_access_schools' => ['sometimes', 'boolean'],
            'can_access_students' => ['sometimes', 'boolean'],
            'can_access_campaigns' => ['sometimes', 'boolean'],
            'can_access_templates' => ['sometimes', 'boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_admin' => false,
            'created_by' => auth()->id(),
            'can_access_schools' => (bool) ($validated['can_access_schools'] ?? false),
            'can_access_students' => (bool) ($validated['can_access_students'] ?? false),
            'can_access_campaigns' => (bool) ($validated['can_access_campaigns'] ?? false),
            'can_access_templates' => (bool) ($validated['can_access_templates'] ?? false),
        ]);

        return redirect()->route('admin.staff.index')
            ->with('success', __('Staff :name has been added.', ['name' => $validated['name']]));
    }

    public function revokeStudents(Request $request, User $staff)
    {
        abort_if($staff->isAdmin(), 404);

        $data = $request->validate([
            'student_ids' => 'array',
            'student_ids.*' => 'integer|exists:students,id',
            'select_all_filtered' => 'nullable|boolean',
        ]);

        $revoked = 0;

        $baseQuery = Student::where('assigned_to', $staff->id);

        // Re-apply filters from the staff detail page so "all filtered" mode is consistent.
        if ($request->filled('lead_status') && in_array($request->input('lead_status'), self::LEAD_STATUSES, true)) {
            $baseQuery->where('lead_status', $request->input('lead_status'));
        }
        if ($request->filled('school_id')) {
            $schoolId = $request->input('school_id');
            $baseQuery->whereHas('classSection', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }
        if ($request->filled('class_section_id')) {
            $baseQuery->where('class_section_id', $request->input('class_section_id'));
        }

        if ($request->boolean('select_all_filtered')) {
            $targetIds = $baseQuery->pluck('id')->all();
        } else {
            $targetIds = $data['student_ids'] ?? [];
        }

        if (empty($targetIds)) {
            return redirect()->route('admin.staff.show', $staff)
                ->with('success', __('No students matched the selected filters.'));
        }

        foreach ($targetIds as $studentId) {
            $student = Student::where('id', $studentId)
                ->where('assigned_to', $staff->id)
                ->first();

            if (! $student) {
                continue;
            }

            $hasCalls = StudentCall::where('student_id', $studentId)
                ->where('user_id', $staff->id)
                ->exists();

            if ($hasCalls) {
                continue;
            }

            $student->update([
                'assigned_to' => null,
                'assigned_by' => null,
                'assigned_at' => null,
                'lead_status' => 'lead',
            ]);
            $revoked++;
        }

        return redirect()->route('admin.staff.show', $staff)
            ->with('success', __(':count student(s) revoked from :name.', ['count' => $revoked, 'name' => $staff->name]));
    }

    public function update(Request $request, User $staff)
    {
        abort_if($staff->isAdmin(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $staff->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'can_access_schools' => ['sometimes', 'boolean'],
            'can_access_students' => ['sometimes', 'boolean'],
            'can_access_campaigns' => ['sometimes', 'boolean'],
            'can_access_templates' => ['sometimes', 'boolean'],
        ]);

        $update = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'can_access_schools' => (bool) ($validated['can_access_schools'] ?? false),
            'can_access_students' => (bool) ($validated['can_access_students'] ?? false),
            'can_access_campaigns' => (bool) ($validated['can_access_campaigns'] ?? false),
            'can_access_templates' => (bool) ($validated['can_access_templates'] ?? false),
        ];

        if (! empty($validated['password'] ?? null)) {
            $update['password'] = Hash::make($validated['password']);
        }

        $staff->update($update);

        return redirect()->route('admin.staff.index')
            ->with('success', __('Staff :name has been updated.', ['name' => $validated['name']]));
    }
}
