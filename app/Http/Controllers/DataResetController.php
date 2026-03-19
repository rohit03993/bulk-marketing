<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AisensyTemplate;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentCall;
use App\Models\StudentImport;
use App\Models\StudentImportColumn;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'student_calls' => StudentCall::count(),
            'tags' => Tag::count(),
            'staff_users' => User::where('is_admin', false)->count(),
        ];

        $schools = School::orderBy('name')->get(['id', 'name']);
        $classSections = ClassSection::with('school')
            ->orderBy('class_name')
            ->orderBy('section_name')
            ->get(['id', 'school_id', 'class_name', 'section_name']);

        return view('admin.reset-data', compact('counts', 'schools', 'classSections'));
    }

    /**
     * Reset all CRM data. Requires confirmation.
     * Keeps admin user accounts and system settings; removes staff logins and all CRM records.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'scope' => ['required', Rule::in(['all', 'school', 'class_section', 'students'])],
            'confirm' => 'required|accepted',
            'confirm_phrase' => ['required', 'in:DELETE DATA NOW'],
            'password' => ['required', 'current_password'],
            'school_ids' => ['nullable', 'array'],
            'school_ids.*' => ['integer', 'exists:schools,id'],
            'class_section_ids' => ['nullable', 'array'],
            'class_section_ids.*' => ['integer', 'exists:class_sections,id'],
            'student_ids' => ['nullable', 'string'],
        ], [
            'confirm.accepted' => __('You must confirm that you want to delete all data.'),
            'confirm_phrase.in' => __('Type exactly :phrase to confirm.', ['phrase' => 'DELETE DATA NOW']),
            'password.current_password' => __('Password is incorrect.'),
        ]);

        $scope = (string) $request->input('scope');
        $schoolIds = collect($request->input('school_ids', []))->map(fn ($v) => (int) $v)->filter()->unique()->values();
        $classSectionIds = collect($request->input('class_section_ids', []))->map(fn ($v) => (int) $v)->filter()->unique()->values();
        $studentIds = collect(preg_split('/[,\s]+/', (string) $request->input('student_ids', ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values();

        if ($scope === 'school' && $schoolIds->isEmpty()) {
            return back()->withErrors(['school_ids' => __('Select at least one school.')])->withInput();
        }
        if ($scope === 'class_section' && $classSectionIds->isEmpty()) {
            return back()->withErrors(['class_section_ids' => __('Select at least one class/section.')])->withInput();
        }
        if ($scope === 'students' && $studentIds->isEmpty()) {
            return back()->withErrors(['student_ids' => __('Enter at least one valid student ID.')])->withInput();
        }

        DB::transaction(function () use ($scope, $schoolIds, $classSectionIds, $studentIds) {
            if ($scope === 'all') {
                // Core CRM data
                CampaignRecipient::query()->delete();
                Campaign::query()->delete();
                StudentCall::query()->delete();
                Student::withTrashed()->forceDelete();
                StudentImportColumn::query()->delete();
                StudentImport::query()->delete();
                ClassSection::query()->delete();
                AcademicSession::query()->delete();
                School::query()->delete();
                AisensyTemplate::query()->delete();
                Tag::query()->delete();
                DB::table('student_tag')->delete();

                // Background jobs / queue state
                if (DB::getSchemaBuilder()->hasTable('jobs')) {
                    DB::table('jobs')->delete();
                }
                if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                    DB::table('failed_jobs')->delete();
                }

                // Remove all non-admin users (staff accounts), keep admins
                User::where('is_admin', false)->delete();

                return;
            }

            if ($scope === 'school') {
                $classIds = ClassSection::whereIn('school_id', $schoolIds)->pluck('id');
                $studentIdsForScope = Student::withTrashed()->whereIn('class_section_id', $classIds)->pluck('id');
                $this->deleteStudentsAndHistory($studentIdsForScope);

                ClassSection::whereIn('id', $classIds)->delete();
                StudentImport::whereIn('school_id', $schoolIds)->delete();
                School::whereIn('id', $schoolIds)->delete();

                return;
            }

            if ($scope === 'class_section') {
                $studentIdsForScope = Student::withTrashed()->whereIn('class_section_id', $classSectionIds)->pluck('id');
                $this->deleteStudentsAndHistory($studentIdsForScope);
                ClassSection::whereIn('id', $classSectionIds)->delete();

                return;
            }

            // scope === 'students'
            $validStudentIds = Student::withTrashed()->whereIn('id', $studentIds)->pluck('id');
            $this->deleteStudentsAndHistory($validStudentIds);
        });

        $message = match ($scope) {
            'all' => __('All CRM data has been deleted. Admin logins and system settings were kept.'),
            'school' => __('Selected school data and related history were deleted.'),
            'class_section' => __('Selected class/section data and related history were deleted.'),
            default => __('Selected students and related history were deleted.'),
        };

        return redirect()->route('admin.dashboard')->with('success', $message);
    }

    /**
     * Delete students and their related history records.
     */
    private function deleteStudentsAndHistory($studentIds): void
    {
        $studentIds = collect($studentIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($studentIds->isEmpty()) {
            return;
        }

        $callIds = StudentCall::whereIn('student_id', $studentIds)->pluck('id');

        if ($callIds->isNotEmpty()) {
            CampaignRecipient::whereIn('student_call_id', $callIds)->delete();
            StudentCall::whereIn('id', $callIds)->delete();
        }

        CampaignRecipient::whereIn('student_id', $studentIds)->delete();
        DB::table('student_tag')->whereIn('student_id', $studentIds)->delete();
        Student::withTrashed()->whereIn('id', $studentIds)->forceDelete();
    }
}
