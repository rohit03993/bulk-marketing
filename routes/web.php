<?php

use App\Http\Controllers\AcademicSessionController;
use App\Http\Controllers\AisensyTemplateController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\PhoneCampaignsController;
use App\Http\Controllers\DataResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\StudentCallController;
use App\Http\Controllers\StudentLeadController;
use App\Http\Controllers\StudentAssignmentController;
use App\Http\Controllers\CallQueueController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        // Admin: global view
        if ($user->isAdmin()) {
            // Reporting scope: fixed academic session 2025-26 only.
            $reportSession = \App\Models\AcademicSession::where('name', '2025-26')->first()
                ?? \App\Models\AcademicSession::orderByDesc('starts_at')->first();
            $reportSessionId = (int) ($reportSession?->id ?? 0);
            $currentSessionName = (string) ($reportSession?->name ?? '2025-26');

            $endOfToday = now()->endOfDay();
            $dueLeadStatuses = ['interested', 'follow_up_later'];
            $convertedStatuses = ['walkin_done', 'admission_done'];

            $hasBlockField = \Illuminate\Support\Facades\Schema::hasColumn('students', 'is_call_blocked');
            $blockedSelect = $hasBlockField
                ? "sum(case when coalesce(st.is_call_blocked,0) = 1 then 1 else 0 end) as blocked_count"
                : "0 as blocked_count";
            $dueSelect = $hasBlockField
                ? "sum(case when st.lead_status in ('" . implode("','", $dueLeadStatuses) . "') and st.next_followup_at is not null and st.next_followup_at <= ? and coalesce(st.is_call_blocked,0) = 0 then 1 else 0 end) as followups_due_count"
                : "sum(case when st.lead_status in ('" . implode("','", $dueLeadStatuses) . "') and st.next_followup_at is not null and st.next_followup_at <= ? then 1 else 0 end) as followups_due_count";
            $convertedSelect = "sum(case when st.lead_status in ('" . implode("','", $convertedStatuses) . "') then 1 else 0 end) as converted_count";

            $schoolAggs = \Illuminate\Support\Facades\DB::table('schools as sch')
                ->leftJoin('class_sections as cs', function ($join) use ($reportSessionId) {
                    $join->on('cs.school_id', '=', 'sch.id')
                        ->where('cs.academic_session_id', '=', $reportSessionId);
                })
                ->leftJoin('students as st', function ($join) {
                    $join->on('st.class_section_id', '=', 'cs.id')
                        ->whereNull('st.deleted_at');
                })
                ->groupBy('sch.id', 'sch.name')
                ->select([
                    'sch.id as school_id',
                    'sch.name as school_name',
                    \Illuminate\Support\Facades\DB::raw('count(st.id) as total_students'),
                    \Illuminate\Support\Facades\DB::raw('count(distinct cs.id) as class_sections_count'),
                    \Illuminate\Support\Facades\DB::raw($convertedSelect),
                    \Illuminate\Support\Facades\DB::raw($blockedSelect),
                ])
                ->selectRaw($dueSelect, [$endOfToday])
                ->orderBy('sch.name')
                ->get()
                ->map(function ($row) {
                    $row->total_students = (int) $row->total_students;
                    $row->class_sections_count = (int) $row->class_sections_count;
                    $row->converted_count = (int) $row->converted_count;
                    $row->followups_due_count = (int) $row->followups_due_count;
                    $row->blocked_count = (int) $row->blocked_count;
                    return $row;
                });

            $classAggs = \Illuminate\Support\Facades\DB::table('class_sections as cs')
                ->join('schools as sch', 'sch.id', '=', 'cs.school_id')
                ->leftJoin('students as st', function ($join) {
                    $join->on('st.class_section_id', '=', 'cs.id')
                        ->whereNull('st.deleted_at');
                })
                ->where('cs.academic_session_id', '=', $reportSessionId)
                ->groupBy('cs.id', 'cs.school_id', 'cs.class_name', 'cs.section_name', 'sch.name')
                ->select([
                    'cs.id as class_section_id',
                    'cs.school_id',
                    'sch.name as school_name',
                    'cs.class_name',
                    'cs.section_name',
                    \Illuminate\Support\Facades\DB::raw('count(st.id) as total_students'),
                    \Illuminate\Support\Facades\DB::raw($convertedSelect),
                    \Illuminate\Support\Facades\DB::raw($blockedSelect),
                ])
                ->selectRaw($dueSelect, [$endOfToday])
                ->orderBy('cs.class_name')
                ->orderBy('cs.section_name')
                ->get()
                ->map(function ($row) {
                    $row->total_students = (int) $row->total_students;
                    $row->converted_count = (int) $row->converted_count;
                    $row->followups_due_count = (int) $row->followups_due_count;
                    $row->blocked_count = (int) $row->blocked_count;
                    return $row;
                });

            $classesBySchoolId = $classAggs->groupBy('school_id');
            $schoolBreakdown = $schoolAggs->map(function ($s) use ($classesBySchoolId) {
                $s->classes = $classesBySchoolId[$s->school_id] ?? collect();
                return $s;
            })->values();

            $telecallerAggs = \Illuminate\Support\Facades\DB::table('users as u')
                ->join('students as st', 'st.assigned_to', '=', 'u.id')
                ->join('class_sections as cs', 'cs.id', '=', 'st.class_section_id')
                ->where('u.is_admin', '=', 0)
                ->where('cs.academic_session_id', '=', $reportSessionId)
                ->whereNull('st.deleted_at')
                ->groupBy('u.id', 'u.name')
                ->select([
                    'u.id as telecaller_id',
                    'u.name as telecaller_name',
                    \Illuminate\Support\Facades\DB::raw('count(st.id) as assigned_students_count'),
                    \Illuminate\Support\Facades\DB::raw($convertedSelect),
                    \Illuminate\Support\Facades\DB::raw($blockedSelect),
                ])
                ->selectRaw($dueSelect, [$endOfToday])
                ->havingRaw('count(st.id) > 0')
                ->orderByDesc('followups_due_count')
                ->get()
                ->map(function ($row) {
                    $row->assigned_students_count = (int) $row->assigned_students_count;
                    $row->converted_count = (int) $row->converted_count;
                    $row->followups_due_count = (int) $row->followups_due_count;
                    $row->blocked_count = (int) $row->blocked_count;
                    return $row;
                });

            $kpi = [
                'total_students' => (int) $schoolAggs->sum('total_students'),
                'converted' => (int) $schoolAggs->sum('converted_count'),
                'followups_due' => (int) $schoolAggs->sum('followups_due_count'),
                'blocked' => (int) $schoolAggs->sum('blocked_count'),
            ];

            $stats = [
                'schools' => \App\Models\School::count(),
                'students' => \App\Models\Student::count(),
                'campaigns' => \App\Models\Campaign::count(),
                'sent' => \App\Models\CampaignRecipient::where('status', 'sent')->count(),
                'pending' => \App\Models\CampaignRecipient::where('status', 'pending')->count(),
                'failed' => \App\Models\CampaignRecipient::where('status', 'failed')->count(),
                'campaigns_completed' => \App\Models\Campaign::where('status', 'completed')->count(),
                'campaigns_draft' => \App\Models\Campaign::where('status', 'draft')->count(),
                'campaigns_in_progress' => \App\Models\Campaign::whereIn('status', ['queued', 'running'])->count(),
            ];
            $schools = \App\Models\School::withCount(['classSections', 'students', 'campaigns'])
                ->orderBy('name')
                ->get();
            $recentCampaignsList = \App\Models\Campaign::with(['school', 'template'])
                ->orderByDesc('updated_at')
                ->take(5)
                ->get();
            $recentIds = $recentCampaignsList->pluck('id')->all();
            $sentCounts = [];
            if (! empty($recentIds)) {
                $rows = \App\Models\CampaignRecipient::whereIn('campaign_id', $recentIds)
                    ->where('status', 'sent')
                    ->selectRaw('campaign_id, count(*) as n')
                    ->groupBy('campaign_id')
                    ->get()
                    ->keyBy('campaign_id');
                foreach ($recentIds as $id) {
                    $sentCounts[$id] = (int) ($rows->get($id)?->n ?? 0);
                }
            }
            $recentCampaigns = $recentCampaignsList->map(function ($c) use ($sentCounts) {
                return (object) ['campaign' => $c, 'sent' => $sentCounts[$c->id] ?? 0, 'total' => $c->total_recipients];
            });
            $mode = 'admin';

            // Telecaller leaderboard (full history: from each telecaller’s first call till now).
            $leaderboardFrom = null;
            $leaderboardToEnd = null;
            $leaderboardTo = now()->copy()->endOfDay();

            // Consider all non-admin staff as potential telecallers for leaderboard
            $telecallers = \App\Models\User::where('is_admin', false)
                ->orderBy('name')
                ->get();

            $leaderboard = [];
            if ($telecallers->isNotEmpty()) {
                $scoreService = new \App\Services\TelecallerScoreService();
                $dailyTarget = 25;

                foreach ($telecallers as $staff) {
                    $firstCalledAt = \App\Models\StudentCall::where('user_id', $staff->id)->min('called_at');
                    if (! $firstCalledAt) {
                        continue;
                    }

                    $leaderboardStart = \Illuminate\Support\Carbon::parse($firstCalledAt)->startOfDay();
                    $score = $scoreService->compute($staff->id, $leaderboardStart, $leaderboardTo, $dailyTarget);

                    $leaderboard[] = [
                        'user' => $staff,
                        'score' => $score['score'],
                        'breakdown' => $score['breakdown'],
                    ];
                }

                usort($leaderboard, function ($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
            }

            return view('dashboard', compact(
                'stats',
                'schools',
                'recentCampaigns',
                'mode',
                'leaderboard',
                'leaderboardFrom',
                'leaderboardToEnd',
                'currentSessionName',
                'kpi',
                'schoolBreakdown',
                'telecallerAggs'
            ));
        }

        // Telecaller / staff: personal dashboard
        $userId = $user->id;
        $now = now();
        $today = $now->toDateString();
        $endOfToday = $now->copy()->endOfDay();
        $upcomingDays = 14;
        $upcomingUntil = $endOfToday->copy()->addDays($upcomingDays);

        $myLeadsQuery = \App\Models\Student::where('assigned_to', $userId);

        // Follow-up pool is strictly these lead statuses.
        $followupLeadStatuses = ['interested', 'follow_up_later'];

        $assignedLeads = (clone $myLeadsQuery)->count();
        $leadWalkin = (clone $myLeadsQuery)->where('lead_status', 'walkin_done')->count();
        $leadAdmission = (clone $myLeadsQuery)->where('lead_status', 'admission_done')->count();
        $leadNotInterested = (clone $myLeadsQuery)->where('lead_status', 'not_interested')->count();

        $followupBase = (clone $myLeadsQuery)
            ->whereIn('lead_status', $followupLeadStatuses)
            ->whereNotNull('next_followup_at');

        $stats = [
            'assigned_leads' => $assignedLeads,
            'lead_new' => (clone $myLeadsQuery)->where('lead_status', 'lead')->where('total_calls', 0)->count(),
            'total_calls_ever' => \App\Models\StudentCall::where('user_id', $userId)->count(),
            'lead_interested' => (clone $myLeadsQuery)->where('lead_status', 'interested')->count(),
            'lead_followup_later' => (clone $myLeadsQuery)->where('lead_status', 'follow_up_later')->count(),
            'lead_walkin_done' => $leadWalkin,
            'lead_admission_done' => $leadAdmission,
            'lead_not_interested' => $leadNotInterested,
            // All active follow-ups in the upcoming window (interested / follow_up_later only)
            'followups_window' => (clone $followupBase)
                ->where('next_followup_at', '<=', $upcomingUntil)
                ->count(),
            // Overdue follow-ups (same pool)
            'overdue_followups' => (clone $followupBase)
                ->whereDate('next_followup_at', '<', $today)
                ->count(),
            'calls_today' => \App\Models\StudentCall::where('user_id', $userId)
                ->whereDate('called_at', $today)
                ->count(),
            'not_connected_today' => \App\Models\StudentCall::where('user_id', $userId)
                ->whereDate('called_at', $today)
                ->whereIn('call_status', [
                    \App\Models\StudentCall::STATUS_NO_ANSWER,
                    \App\Models\StudentCall::STATUS_BUSY,
                    \App\Models\StudentCall::STATUS_SWITCHED_OFF,
                    \App\Models\StudentCall::STATUS_NOT_REACHABLE,
                    \App\Models\StudentCall::STATUS_WRONG_NUMBER,
                    \App\Models\StudentCall::STATUS_CALLBACK,
                ])
                ->count(),
            'messages_sent' => \App\Models\CampaignRecipient::where('status', 'sent')
                ->whereHas('campaign', fn ($q) => $q->where('shot_by', $userId))
                ->count(),
        ];

        // Telecaller rating (auto-updates from call history)
        $scoreService = new \App\Services\TelecallerScoreService();
        $dailyTarget = 25;
        $scoreToday = $scoreService->compute($userId, $now->copy(), $now->copy(), $dailyTarget);
        $scoreOverall = $scoreService->computeOverallAverage($userId, $dailyTarget);
        $stats['score_today'] = $scoreToday;
        $stats['score_overall'] = $scoreOverall;

        $recentCampaignsList = \App\Models\Campaign::with(['school', 'template'])
            ->where('shot_by', $userId)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();
        $recentIds = $recentCampaignsList->pluck('id')->all();
        $sentCounts = [];
        if (! empty($recentIds)) {
            $rows = \App\Models\CampaignRecipient::whereIn('campaign_id', $recentIds)
                ->where('status', 'sent')
                ->selectRaw('campaign_id, count(*) as n')
                ->groupBy('campaign_id')
                ->get()
                ->keyBy('campaign_id');
            foreach ($recentIds as $id) {
                $sentCounts[$id] = (int) ($rows->get($id)?->n ?? 0);
            }
        }
        $recentCampaigns = $recentCampaignsList->map(function ($c) use ($sentCounts) {
            return (object) ['campaign' => $c, 'sent' => $sentCounts[$c->id] ?? 0, 'total' => $c->total_recipients];
        });
        $mode = 'telecaller';
        $schools = collect();

        return view('dashboard', compact('stats', 'schools', 'recentCampaigns', 'mode'));
    })->name('dashboard');

    Route::middleware('access:schools')->group(function () {
        Route::resource('schools', SchoolController::class)->except('show', 'destroy');
        Route::resource('sessions', AcademicSessionController::class)->except('show', 'destroy')->parameters(['session' => 'academic_session']);
        Route::resource('class-sections', ClassSectionController::class)->except('show', 'destroy');
        Route::post('class-sections/presets/neet-jee', [ClassSectionController::class, 'addNeetJeePreset'])->name('class-sections.presets.neet-jee');
        Route::resource('students', StudentController::class)->except('show', 'destroy');
        Route::get('students-assign', [StudentAssignmentController::class, 'form'])->name('students.assign');
        Route::post('students-assign', [StudentAssignmentController::class, 'bulkAssign'])->name('students.assign.perform');

        Route::get('student-imports', [StudentImportController::class, 'index'])->name('student-imports.index');
        Route::get('student-imports/create', [StudentImportController::class, 'create'])->name('student-imports.create');
        Route::post('student-imports', [StudentImportController::class, 'store'])->name('student-imports.store');
        Route::get('student-imports/{student_import}/mapping', [StudentImportController::class, 'mapping'])->name('student-imports.mapping');
        Route::post('student-imports/{student_import}/mapping', [StudentImportController::class, 'saveMapping'])->name('student-imports.save-mapping');
        Route::get('student-imports/{student_import}/process', [StudentImportController::class, 'process'])->name('student-imports.process');
        Route::get('student-imports/{student_import}/report', [StudentImportController::class, 'report'])->name('student-imports.report');
    });

    // Telecaller access (students/Leads)
    Route::middleware('access:students')->group(function () {
        Route::get('call-report', [\App\Http\Controllers\CallReportController::class, 'index'])->name('calls.report');
        Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('students/{student}/profile-edit', [StudentController::class, 'profileEdit'])->name('students.profile.edit');
        Route::patch('students/{student}/profile-edit', [StudentController::class, 'profileUpdate'])->name('students.profile.update');
        Route::post('students/{student}/send-single', [StudentController::class, 'sendSingleMessage'])->name('students.send-single');
        Route::patch('students/{student}/lead-status', [StudentController::class, 'updateLeadStatus'])->name('students.update-lead-status');
        Route::post('students/{student}/calls', [StudentCallController::class, 'store'])->name('students.calls.store');
        Route::post('students/call/suggest-followup', [StudentCallController::class, 'suggestFollowup'])->name('students.call.suggest-followup');
        Route::get('my-leads', [StudentLeadController::class, 'myLeads'])->name('students.my-leads');
        Route::post('my-leads/add', [StudentLeadController::class, 'addLead'])->name('students.my-leads.add');
        Route::get('followups', [StudentLeadController::class, 'followups'])->name('students.followups');
        // Call queue (Start Calling) – one lead at a time
        Route::get('call-queue', [CallQueueController::class, 'index'])->name('students.call-queue');
        Route::get('call-queue/next', [CallQueueController::class, 'getNext'])->name('students.call-queue.next');
        Route::get('students/{student}/call-queue-data', [CallQueueController::class, 'getLeadData'])->name('students.call-queue.data');
    });

    Route::middleware('access:templates')->group(function () {
        Route::resource('templates', AisensyTemplateController::class)->except('show', 'destroy');
    });

    Route::middleware('access:campaigns')->group(function () {
        Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::get('campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
        Route::post('campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
        Route::get('campaigns/stats', [CampaignController::class, 'statsBulk'])->name('campaigns.stats.bulk');
        Route::post('campaigns/{campaign}/shoot', [CampaignController::class, 'shoot'])->name('campaigns.shoot');
        Route::post('campaigns/{campaign}/stop', [CampaignController::class, 'stop'])->name('campaigns.stop');
        Route::post('campaigns/{campaign}/resume', [CampaignController::class, 'resume'])->name('campaigns.resume');
        Route::get('campaigns/{campaign}/stats', [CampaignController::class, 'stats'])->name('campaigns.stats');
        Route::get('campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
    });

    Route::get('phone/{phone}/campaigns', [PhoneCampaignsController::class, 'show'])->name('phone.campaigns')->where('phone', '[0-9]{10}');
    Route::post('phone/{phone}/send-single', [PhoneCampaignsController::class, 'sendSingle'])->name('phone.send-single')->where('phone', '[0-9]{10}');

    // Admin section: full access overview (admin only)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('staff/{staff}', [StaffController::class, 'show'])->name('staff.show');
        Route::get('staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
        Route::patch('staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
        Route::post('staff/{staff}/revoke-students', [StaffController::class, 'revokeStudents'])->name('staff.revoke-students');
        Route::get('settings/postcall-whatsapp', [\App\Http\Controllers\AdminSettingsController::class, 'postcallWhatsapp'])->name('settings.postcall-whatsapp');
        Route::post('settings/postcall-whatsapp', [\App\Http\Controllers\AdminSettingsController::class, 'postcallWhatsappUpdate'])->name('settings.postcall-whatsapp.update');

        // Lead class presets for tellcaller (fixed NEET/JEE options, admin-configurable)
        Route::get('lead-class-presets', [\App\Http\Controllers\LeadClassPresetController::class, 'index'])->name('lead-class-presets.index');
        Route::post('lead-class-presets', [\App\Http\Controllers\LeadClassPresetController::class, 'store'])->name('lead-class-presets.store');
        Route::post('lead-class-presets/{preset}/toggle', [\App\Http\Controllers\LeadClassPresetController::class, 'toggle'])->name('lead-class-presets.toggle');

        // Hidden danger-zone route for destructive data operations.
        Route::get('ops/danger-zone/data-reset', [DataResetController::class, 'showResetForm'])->name('reset-data');
        Route::post('ops/danger-zone/data-reset', [DataResetController::class, 'reset'])->name('reset-data.perform');
        Route::get('/', function () {
            // Admin "Reports" dashboard (read-only). Fixed to academic session `2025-26` only.
            $session = \App\Models\AcademicSession::where('name', '2025-26')->first()
                ?? \App\Models\AcademicSession::orderByDesc('starts_at')->first();

            $sessionId = (int) ($session?->id ?? 0);
            $sessionName = (string) ($session?->name ?? '2025-26');

            $endOfToday = now()->endOfDay();

            $dueLeadStatuses = ['interested', 'follow_up_later'];
            $convertedStatuses = ['walkin_done', 'admission_done'];

            $hasBlockField = \Illuminate\Support\Facades\Schema::hasColumn('students', 'is_call_blocked');

            // If `is_call_blocked` isn't present for some reason, fall back to "blocked_count = 0" and don't exclude from due counts.
            $blockedSelect = $hasBlockField
                ? "sum(case when coalesce(st.is_call_blocked,0) = 1 then 1 else 0 end) as blocked_count"
                : "0 as blocked_count";

            $dueSelect = $hasBlockField
                ? "sum(case when st.lead_status in ('" . implode("','", $dueLeadStatuses) . "') and st.next_followup_at is not null and st.next_followup_at <= ? and coalesce(st.is_call_blocked,0) = 0 then 1 else 0 end) as followups_due_count"
                : "sum(case when st.lead_status in ('" . implode("','", $dueLeadStatuses) . "') and st.next_followup_at is not null and st.next_followup_at <= ? then 1 else 0 end) as followups_due_count";

            $convertedSelect = "sum(case when st.lead_status in ('" . implode("','", $convertedStatuses) . "') then 1 else 0 end) as converted_count";

            // School -> totals for the current session.
            $schoolAggs = \Illuminate\Support\Facades\DB::table('schools as sch')
                ->leftJoin('class_sections as cs', function ($join) use ($sessionId) {
                    $join->on('cs.school_id', '=', 'sch.id')
                        ->where('cs.academic_session_id', '=', $sessionId);
                })
                ->leftJoin('students as st', function ($join) {
                    $join->on('st.class_section_id', '=', 'cs.id')
                        ->whereNull('st.deleted_at');
                })
                ->groupBy('sch.id', 'sch.name')
                ->select([
                    'sch.id as school_id',
                    'sch.name as school_name',
                    \Illuminate\Support\Facades\DB::raw('count(st.id) as total_students'),
                    \Illuminate\Support\Facades\DB::raw('count(distinct cs.id) as class_sections_count'),
                    \Illuminate\Support\Facades\DB::raw($convertedSelect),
                    \Illuminate\Support\Facades\DB::raw($blockedSelect),
                ])
                ->selectRaw($dueSelect, [$endOfToday])
                ->orderBy('sch.name')
                ->get();

            $schoolAggs = $schoolAggs->map(function ($row) {
                $row->total_students = (int) $row->total_students;
                $row->class_sections_count = (int) $row->class_sections_count;
                $row->converted_count = (int) $row->converted_count;
                $row->followups_due_count = (int) $row->followups_due_count;
                $row->blocked_count = (int) $row->blocked_count;
                return $row;
            });

            // Class/Section breakdown for the current session.
            $classAggs = \Illuminate\Support\Facades\DB::table('class_sections as cs')
                ->join('schools as sch', 'sch.id', '=', 'cs.school_id')
                ->leftJoin('students as st', function ($join) {
                    $join->on('st.class_section_id', '=', 'cs.id')
                        ->whereNull('st.deleted_at');
                })
                ->where('cs.academic_session_id', '=', $sessionId)
                ->groupBy('cs.id', 'cs.school_id', 'cs.class_name', 'cs.section_name', 'sch.name')
                ->select([
                    'cs.id as class_section_id',
                    'cs.school_id',
                    'sch.name as school_name',
                    'cs.class_name',
                    'cs.section_name',
                    \Illuminate\Support\Facades\DB::raw('count(st.id) as total_students'),
                    \Illuminate\Support\Facades\DB::raw($convertedSelect),
                    \Illuminate\Support\Facades\DB::raw($blockedSelect),
                ])
                ->selectRaw($dueSelect, [$endOfToday])
                ->orderBy('cs.class_name')
                ->orderBy('cs.section_name')
                ->get()
                ->map(function ($row) {
                    $row->total_students = (int) $row->total_students;
                    $row->converted_count = (int) $row->converted_count;
                    $row->followups_due_count = (int) $row->followups_due_count;
                    $row->blocked_count = (int) $row->blocked_count;
                    return $row;
                });

            $classesBySchoolId = $classAggs->groupBy('school_id');

            // Telecaller breakdown for follow-ups due (overall) within the current session.
            $telecallerAggs = \Illuminate\Support\Facades\DB::table('users as u')
                ->join('students as st', 'st.assigned_to', '=', 'u.id')
                ->join('class_sections as cs', 'cs.id', '=', 'st.class_section_id')
                ->where('u.is_admin', '=', 0)
                ->where('cs.academic_session_id', '=', $sessionId)
                ->whereNull('st.deleted_at')
                ->groupBy('u.id', 'u.name')
                ->select([
                    'u.id as telecaller_id',
                    'u.name as telecaller_name',
                    \Illuminate\Support\Facades\DB::raw('count(st.id) as assigned_students_count'),
                    \Illuminate\Support\Facades\DB::raw($convertedSelect),
                    \Illuminate\Support\Facades\DB::raw($blockedSelect),
                ])
                ->selectRaw($dueSelect, [$endOfToday])
                ->havingRaw('count(st.id) > 0')
                ->orderByDesc('followups_due_count')
                ->get()
                ->map(function ($row) {
                    $row->assigned_students_count = (int) $row->assigned_students_count;
                    $row->converted_count = (int) $row->converted_count;
                    $row->followups_due_count = (int) $row->followups_due_count;
                    $row->blocked_count = (int) $row->blocked_count;
                    return $row;
                });

            $firstTelecallerId = $telecallerAggs->first()->telecaller_id ?? 0;

            $kpi = [
                'total_students' => (int) $schoolAggs->sum('total_students'),
                'converted' => (int) $schoolAggs->sum('converted_count'),
                'followups_due' => (int) $schoolAggs->sum('followups_due_count'),
                'blocked' => (int) $schoolAggs->sum('blocked_count'),
            ];

            $schoolBreakdown = $schoolAggs->map(function ($s) use ($classesBySchoolId) {
                $s->classes = $classesBySchoolId[$s->school_id] ?? collect();
                return $s;
            })->values();

            return view('admin.dashboard', [
                'currentSessionName' => $sessionName,
                'kpi' => $kpi,
                'schoolBreakdown' => $schoolBreakdown,
                'telecallerAggs' => $telecallerAggs,
                'firstTelecallerId' => $firstTelecallerId,
            ]);
        })->name('dashboard');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
