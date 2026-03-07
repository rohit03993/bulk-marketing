<?php

use App\Http\Controllers\AcademicSessionController;
use App\Http\Controllers\AisensyTemplateController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\PhoneCampaignsController;
use App\Http\Controllers\DataResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
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
        return view('dashboard', compact('stats', 'schools', 'recentCampaigns'));
    })->name('dashboard');

    Route::resource('schools', SchoolController::class)->except('show', 'destroy');
    Route::resource('sessions', AcademicSessionController::class)->except('show', 'destroy')->parameters(['session' => 'academic_session']);
    Route::resource('class-sections', ClassSectionController::class)->except('show', 'destroy');
    Route::resource('students', StudentController::class)->except('show', 'destroy');

    Route::get('student-imports', [StudentImportController::class, 'index'])->name('student-imports.index');
    Route::get('student-imports/create', [StudentImportController::class, 'create'])->name('student-imports.create');
    Route::post('student-imports', [StudentImportController::class, 'store'])->name('student-imports.store');
    Route::get('student-imports/{student_import}/mapping', [StudentImportController::class, 'mapping'])->name('student-imports.mapping');
    Route::post('student-imports/{student_import}/mapping', [StudentImportController::class, 'saveMapping'])->name('student-imports.save-mapping');
    Route::get('student-imports/{student_import}/process', [StudentImportController::class, 'process'])->name('student-imports.process');

    Route::resource('templates', AisensyTemplateController::class)->except('show', 'destroy');

    Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('campaigns/stats', [CampaignController::class, 'statsBulk'])->name('campaigns.stats.bulk');
    Route::post('campaigns/{campaign}/shoot', [CampaignController::class, 'shoot'])->name('campaigns.shoot');
    Route::get('campaigns/{campaign}/stats', [CampaignController::class, 'stats'])->name('campaigns.stats');
    Route::get('campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
    Route::get('phone/{phone}/campaigns', [PhoneCampaignsController::class, 'show'])->name('phone.campaigns')->where('phone', '[0-9]{10}');

    // Admin section: full access overview (admin only)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
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
