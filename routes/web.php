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

            // Telecaller leaderboard (last 7 days, based on same scoring as telecaller view)
            $leaderboardDays = 7;
            $leaderboardTo = now();
            $leaderboardFrom = $leaderboardTo->copy()->subDays($leaderboardDays - 1)->startOfDay();
            $leaderboardToEnd = $leaderboardTo->copy()->endOfDay();

            // Consider all non-admin staff as potential telecallers for leaderboard
            $telecallers = \App\Models\User::where('is_admin', false)
                ->orderBy('name')
                ->get();

            $leaderboard = [];
            if ($telecallers->isNotEmpty()) {
                $scoreService = new \App\Services\TelecallerScoreService();
                $dailyTarget = 25;

                foreach ($telecallers as $staff) {
                    $score = $scoreService->compute($staff->id, $leaderboardFrom, $leaderboardToEnd, $dailyTarget);

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

            return view('dashboard', compact('stats', 'schools', 'recentCampaigns', 'mode', 'leaderboard', 'leaderboardFrom', 'leaderboardToEnd'));
        }

        // Telecaller / staff: personal dashboard
        $userId = $user->id;
        $now = now();
        $today = $now->toDateString();
        $endOfToday = $now->copy()->endOfDay();
        $upcomingDays = 14;
        $upcomingUntil = $endOfToday->copy()->addDays($upcomingDays);

        $myLeadsQuery = \App\Models\Student::where('assigned_to', $userId);
        $stats = [
            'assigned_leads' => (clone $myLeadsQuery)->count(),
            'lead_new' => (clone $myLeadsQuery)->where('lead_status', 'lead')->where('total_calls', 0)->count(),
            'total_calls_ever' => \App\Models\StudentCall::where('user_id', $userId)->count(),
            'lead_interested' => (clone $myLeadsQuery)->where('lead_status', 'interested')->count(),
            'lead_followup_later' => (clone $myLeadsQuery)->where('lead_status', 'follow_up_later')->count(),
            'lead_walkin_done' => (clone $myLeadsQuery)->where('lead_status', 'walkin_done')->count(),
            'lead_admission_done' => (clone $myLeadsQuery)->where('lead_status', 'admission_done')->count(),
            'lead_not_interested' => (clone $myLeadsQuery)->where('lead_status', 'not_interested')->count(),
            'followups_window' => (clone $myLeadsQuery)
                ->whereNotNull('next_followup_at')
                ->where('next_followup_at', '<=', $upcomingUntil)
                ->count(),
            'overdue_followups' => (clone $myLeadsQuery)
                ->whereNotNull('next_followup_at')
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
        Route::get('reset-data', [DataResetController::class, 'showResetForm'])->name('reset-data');
        Route::post('reset-data', [DataResetController::class, 'reset'])->name('reset-data.perform');
        Route::get('/', function () {
            $schools = \App\Models\School::withCount('classSections')->orderBy('name')->get();
            $sessions = \App\Models\AcademicSession::orderByDesc('starts_at')->get();
            $recentCampaigns = \App\Models\Campaign::with('school', 'template')->orderByDesc('created_at')->take(10)->get();
            $recentImports = \App\Models\StudentImport::with('school')->orderByDesc('created_at')->take(5)->get();
            $stats = [
                'schools' => \App\Models\School::count(),
                'sessions' => \App\Models\AcademicSession::count(),
                'class_sections' => \App\Models\ClassSection::count(),
                'students' => \App\Models\Student::count(),
                'templates' => \App\Models\AisensyTemplate::count(),
                'campaigns' => \App\Models\Campaign::count(),
                'messages_sent' => \App\Models\CampaignRecipient::where('status', 'sent')->count(),
            ];
            return view('admin.dashboard', compact('schools', 'sessions', 'recentCampaigns', 'recentImports', 'stats'));
        })->name('dashboard');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
