<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\StudentCall;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\School;
use App\Models\ClassSection;
use App\Models\StudentAssignmentTransfer;
use App\Services\TelecallerScoreService;
use App\Services\TelecallerCallReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Carbon;

class StaffController extends Controller
{
    private const REVOKE_LATEST_BATCH = 10;
    private const LEAD_STATUSES = ['lead', 'interested', 'not_interested', 'walkin_done', 'admission_done', 'follow_up_later', 'converted'];

    public function show(Request $request, User $staff)
    {
        abort_if($staff->isAdmin(), 404);

        $now = now();
        $today = $now->copy();

        // Reporting session for admin call reporting (locked to 2025-26 by default).
        $reportSessionId = \App\Models\AcademicSession::where('name', '2025-26')->value('id')
            ?? \App\Models\AcademicSession::where('is_current', true)->value('id')
            ?? \App\Models\AcademicSession::orderByDesc('starts_at')->value('id');

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
        $filterAddedByMe = $request->boolean('added_by_me');

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

        // Telecaller call reporting (Pending + New vs Follow-up).
        $reportService = new TelecallerCallReportingService();
        $pending = $reportService->pendingCallsCounts([$staff->id], (int) $reportSessionId, now())[$staff->id] ?? [
            'pending_total' => 0,
            'pending_new' => 0,
            'pending_followup' => 0,
        ];

        // Keep UI light: cap daily table to last 14 days.
        $maxDays = 14;
        if ($rangeEnd->diffInDays($rangeStart) + 1 > $maxDays) {
            $rangeStart = $rangeEnd->copy()->subDays($maxDays - 1)->startOfDay();
        }

        $dailySplit = $reportService->dailyCallsSplit(
            [$staff->id],
            (int) $reportSessionId,
            $rangeStart,
            $rangeEnd
        );

        // Normalize into rows: [date, new_calls, followup_calls, total_calls]
        $dailySplitRows = [];
        $callsRangeTotals = ['new_calls' => 0, 'followup_calls' => 0, 'total_calls' => 0];
        foreach ($dailySplit as $date => $byTelecaller) {
            $c = $byTelecaller[$staff->id] ?? ['new_calls' => 0, 'followup_calls' => 0, 'total_calls' => 0];
            $c['new_calls'] = (int) ($c['new_calls'] ?? 0);
            $c['followup_calls'] = (int) ($c['followup_calls'] ?? 0);
            $c['total_calls'] = (int) ($c['total_calls'] ?? 0);
            $dailySplitRows[] = [
                'date' => $date,
                'new_calls' => $c['new_calls'],
                'followup_calls' => $c['followup_calls'],
                'total_calls' => $c['total_calls'],
            ];
            $callsRangeTotals['new_calls'] += $c['new_calls'];
            $callsRangeTotals['followup_calls'] += $c['followup_calls'];
            $callsRangeTotals['total_calls'] += $c['total_calls'];
        }
        usort($dailySplitRows, fn ($a, $b) => strcmp($b['date'], $a['date']));

        $telecallerOptions = \App\Models\User::where('is_admin', false)->orderBy('name')->get();

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
            ->when($filterAddedByMe, fn ($q) => $q->where('assigned_by', $staff->id))
            ->orderByDesc('assigned_at')
            ->orderByDesc('id');
        if ($filterLeadStatus !== null) {
            if ($filterLeadStatus === 'converted') {
                $studentsQuery->whereIn('lead_status', ['walkin_done', 'admission_done']);
            } else {
                $studentsQuery->where('lead_status', $filterLeadStatus);
            }
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
        $uncalledStudentIdsAll = $allAssignedIds
            ->filter(fn ($id) => empty($callCountsByStudent[$id] ?? 0))
            ->values()
            ->all();

        $students = $studentsQuery->paginate(10, ['*'], 'students_page')->withQueryString();

        $campaigns = Campaign::with(['school', 'template'])
            ->where('shot_by', $staff->id)
            ->orderByDesc('created_at')
            ->paginate(5, ['*'], 'campaigns_page')
            ->withQueryString();

        $messagesSent = CampaignRecipient::where('status', 'sent')
            ->whereHas('campaign', fn ($q) => $q->where('shot_by', $staff->id))
            ->count();

        $assignmentActivity = StudentAssignmentTransfer::with([
            'student.classSection.school',
            'fromUser',
            'toUser',
            'transferredByUser',
        ])
            ->where(function ($q) use ($staff) {
                $q->where('to_user_id', $staff->id)
                    ->orWhere('from_user_id', $staff->id);
            })
            ->when($filterFrom && $filterTo && $filterFrom->lte($filterTo), function ($q) use ($filterFrom, $filterTo) {
                $q->whereBetween('transferred_at', [$filterFrom, $filterTo]);
            })
            ->when($filterSchoolId, function ($q) use ($filterSchoolId) {
                $q->whereHas('student.classSection', fn ($sq) => $sq->where('school_id', $filterSchoolId));
            })
            ->orderByDesc('transferred_at')
            ->paginate(10, ['*'], 'assignment_page')
            ->withQueryString();

        $leadStatusOptions = [
            'lead' => __('Uncalled'),
            'interested' => __('Interested'),
            'converted' => __('Converted (Walk-in + Admission)'),
            'not_interested' => __('Not Interested'),
            'walkin_done' => __('Walk-in Done'),
            'admission_done' => __('Admission Done'),
            'follow_up_later' => __('Follow-up Later'),
        ];

        // Lifetime stats for this telecaller (optionally filtered by "added_by_me").
        $assignedColumn = $filterAddedByMe ? 'assigned_by' : 'assigned_to';
        $assignedTotal = Student::where($assignedColumn, $staff->id)->count();
        $convertedWalkin = Student::where($assignedColumn, $staff->id)
            ->where('lead_status', 'walkin_done')
            ->count();
        $convertedAdmission = Student::where($assignedColumn, $staff->id)
            ->where('lead_status', 'admission_done')
            ->count();
        $exitedNotInterested = Student::where($assignedColumn, $staff->id)
            ->where('lead_status', 'not_interested')
            ->count();

        $schools = School::orderBy('name')->get();
        $classSections = collect();
        if ($filterSchoolId) {
            $classSections = ClassSection::where('school_id', $filterSchoolId)
                ->orderBy('class_name')
                ->orderBy('section_name')
                ->get();
        }

        // Schools + eligible uncalled counts for "Revoke latest (school)".
        $revokeEligibleBySchool = Student::query()
            ->where('assigned_to', $staff->id)
            ->when($filterClassSectionId, fn ($q) => $q->where('class_section_id', $filterClassSectionId))
            ->when($filterLeadStatus, function ($q) use ($filterLeadStatus) {
                if ($filterLeadStatus === 'converted') {
                    $q->whereIn('lead_status', ['walkin_done', 'admission_done']);
                } else {
                    $q->where('lead_status', $filterLeadStatus);
                }
            })
            ->whereDoesntHave('calls', fn ($q) => $q->where('user_id', $staff->id))
            ->join('class_sections', 'students.class_section_id', '=', 'class_sections.id')
            ->selectRaw('class_sections.school_id as school_id, count(students.id) as eligible_count')
            ->groupBy('class_sections.school_id')
            ->pluck('eligible_count', 'school_id');

        $revokeSchoolOptions = School::query()
            ->whereIn('id', $revokeEligibleBySchool->keys())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($s) use ($revokeEligibleBySchool) {
                $s->eligible_count = (int) ($revokeEligibleBySchool[$s->id] ?? 0);
                return $s;
            })
            ->values();

        return view('admin.staff.show', [
            'staff' => $staff,
            'scoreToday' => $scoreToday,
            'scoreOverall' => $scoreOverall,
            'pendingCalls' => $pending,
            'callsRangeTotals' => $callsRangeTotals,
            'dailyNewFollowup' => $dailySplitRows,
            'telecallerOptions' => $telecallerOptions,
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
            'uncalledStudentIdsAll' => $uncalledStudentIdsAll,
            'campaigns' => $campaigns,
            'assignmentActivity' => $assignmentActivity,
            'messagesSent' => $messagesSent,
            'assignedTotal' => $assignedTotal,
            'convertedWalkin' => $convertedWalkin,
            'convertedAdmission' => $convertedAdmission,
            'exitedNotInterested' => $exitedNotInterested,
            'filterFrom' => $filterFrom?->toDateString(),
            'filterTo' => $filterTo?->toDateString(),
            'filterLeadStatus' => $filterLeadStatus,
            'leadStatusOptions' => $leadStatusOptions,
            'schools' => $schools,
            'revokeSchoolOptions' => $revokeSchoolOptions,
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
            'revoke_latest' => 'nullable|boolean',
        ]);

        $revoked = 0;

        $baseQuery = Student::where('assigned_to', $staff->id);

        // Re-apply filters from the staff detail page so "all filtered" mode is consistent.
        if ($request->filled('lead_status') && in_array($request->input('lead_status'), self::LEAD_STATUSES, true)) {
            if ($request->input('lead_status') === 'converted') {
                $baseQuery->whereIn('lead_status', ['walkin_done', 'admission_done']);
            } else {
                $baseQuery->where('lead_status', $request->input('lead_status'));
            }
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

        if ($request->boolean('revoke_latest')) {
            // Safety: latest revoke requires at least school filter scope.
            if (! $request->filled('school_id')) {
                return redirect()->route('admin.staff.show', $staff)
                    ->with('error', __('Please select a School before revoking latest students.'));
            }

            $latestCount = self::REVOKE_LATEST_BATCH;

            // Start from latest assignments; optional class/lead filters are already in baseQuery.
            $candidateStudents = (clone $baseQuery)
                ->orderByDesc('assigned_at')
                ->orderByDesc('id')
                ->limit($latestCount * 3) // buffer because some may have calls and be skipped
                ->get(['id']);

            $targetIds = [];
            foreach ($candidateStudents as $s) {
                $hasCalls = StudentCall::where('student_id', $s->id)
                    ->where('user_id', $staff->id)
                    ->exists();

                if (! $hasCalls) {
                    $targetIds[] = (int) $s->id;
                }

                if (count($targetIds) >= $latestCount) {
                    break;
                }
            }
        } elseif ($request->boolean('select_all_filtered')) {
            // Extra safety: only allow bulk revoke when scoped to a specific school + class.
            if (! $request->filled('school_id') || ! $request->filled('class_section_id')) {
                return redirect()->route('admin.staff.show', $staff)
                    ->with('error', __('Please select a School and Class before revoking all uncalled students.'));
            }

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
