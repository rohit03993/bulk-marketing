<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AisensyTemplate;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentImport;
use App\Models\StudentImportColumn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataResetController extends Controller
{
    /**
     * Show the confirmation form for resetting all CRM data.
     * Does not touch: users, sessions, settings.
     */
    public function showResetForm()
    {
        $counts = [
            'schools' => School::count(),
            'sessions' => AcademicSession::count(),
            'class_sections' => ClassSection::count(),
            'students' => Student::withTrashed()->count(),
            'templates' => AisensyTemplate::count(),
            'campaigns' => Campaign::count(),
            'imports' => StudentImport::count(),
        ];

        return view('admin.reset-data', compact('counts'));
    }

    /**
     * Reset all CRM data. Requires confirmation. Keeps users and settings.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'confirm' => 'required|accepted',
            'confirm_phrase' => ['required', 'in:RESET ALL DATA'],
        ], [
            'confirm.accepted' => __('You must confirm that you want to delete all data.'),
            'confirm_phrase.in' => __('Type exactly :phrase to confirm.', ['phrase' => 'RESET ALL DATA']),
        ]);

        DB::transaction(function () {
            CampaignRecipient::query()->delete();
            Campaign::query()->delete();
            Student::withTrashed()->forceDelete();
            StudentImportColumn::query()->delete();
            StudentImport::query()->delete();
            ClassSection::query()->delete();
            AcademicSession::query()->delete();
            School::query()->delete();
            AisensyTemplate::query()->delete();
        });

        return redirect()->route('admin.dashboard')
            ->with('success', __('All CRM data has been deleted. Schools, students, classes, sessions, templates, campaigns and imports are reset. User accounts and settings were kept.'));
    }
}
