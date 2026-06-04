<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AisensyTemplate;
use App\Models\Campaign;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentAssignmentTransfer;
use App\Models\StudentCall;
use App\Models\StudentImport;
use App\Models\Tag;
use App\Models\User;
use App\Services\CrmDataResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DataResetController extends Controller
{
    public function __construct(
        private readonly CrmDataResetService $crmDataReset
    ) {}

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
            'assignment_transfers' => StudentAssignmentTransfer::count(),
            'tags' => Tag::count(),
            'staff_users' => User::where('is_admin', false)->count(),
        ];

        $schools = School::orderBy('name')->get(['id', 'name']);
        $schoolDeletionPreview = collect(
            $this->crmDataReset->schoolDeletionPreview($schools->pluck('id'))
        )->values();
        $classSections = ClassSection::with('school')
            ->orderBy('class_name')
            ->orderBy('section_name')
            ->get(['id', 'school_id', 'class_name', 'section_name']);

        return view('admin.reset-data', compact('counts', 'schools', 'classSections', 'schoolDeletionPreview'));
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

        if ($scope === 'school') {
            $blocking = $this->crmDataReset->assignedCalledStudentsInSchools($schoolIds);
            if ($blocking->isNotEmpty()) {
                $sampleIds = $blocking->take(15)->pluck('id')->join(', ');
                $extra = $blocking->count() > 15
                    ? ' '.__('and :count more', ['count' => $blocking->count() - 15])
                    : '';

                return back()->withErrors([
                    'school_ids' => __(
                        'Cannot delete: :count student(s) in this school are assigned to staff and already have call history. Only uncalled assigned leads (or unassigned students) can be removed with a school delete. Reassign or resolve those leads first. Student IDs: :ids:extra',
                        [
                            'count' => $blocking->count(),
                            'ids' => $sampleIds,
                            'extra' => $extra,
                        ]
                    ),
                ])->withInput();
            }
        }
        if ($scope === 'class_section' && $classSectionIds->isEmpty()) {
            return back()->withErrors(['class_section_ids' => __('Select at least one class/section.')])->withInput();
        }
        if ($scope === 'students' && $studentIds->isEmpty()) {
            return back()->withErrors(['student_ids' => __('Enter at least one valid student ID.')])->withInput();
        }

        $actingUserId = (int) $request->user()->id;

        DB::transaction(function () use ($scope, $schoolIds, $classSectionIds, $studentIds, $actingUserId) {
            if ($scope === 'all') {
                $this->crmDataReset->wipeAll($actingUserId);

                return;
            }

            if ($scope === 'school') {
                $classIds = ClassSection::whereIn('school_id', $schoolIds)->pluck('id');
                $studentIdsForScope = Student::withTrashed()->whereIn('class_section_id', $classIds)->pluck('id');
                $this->crmDataReset->deleteStudentsAndHistory($studentIdsForScope);

                ClassSection::whereIn('id', $classIds)->delete();
                StudentImport::whereIn('school_id', $schoolIds)->delete();
                School::whereIn('id', $schoolIds)->delete();

                return;
            }

            if ($scope === 'class_section') {
                $studentIdsForScope = Student::withTrashed()->whereIn('class_section_id', $classSectionIds)->pluck('id');
                $this->crmDataReset->deleteStudentsAndHistory($studentIdsForScope);
                ClassSection::whereIn('id', $classSectionIds)->delete();

                return;
            }

            $validStudentIds = Student::withTrashed()->whereIn('id', $studentIds)->pluck('id');
            $this->crmDataReset->deleteStudentsAndHistory($validStudentIds);
        });

        $message = match ($scope) {
            'all' => __('All CRM data has been deleted. Admin logins and system settings were kept.'),
            'school' => __('Selected school data and related history were deleted.'),
            'class_section' => __('Selected class/section data and related history were deleted.'),
            default => __('Selected students and related history were deleted.'),
        };

        return redirect()->route('admin.dashboard')->with('success', $message);
    }
}
