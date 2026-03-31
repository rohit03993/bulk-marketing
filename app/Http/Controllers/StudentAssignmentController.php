<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentAssignmentTransfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentAssignmentController extends Controller
{
    public function form(Request $request)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $query = Student::with(['classSection.school'])
            ->orderBy('name');

        if ($request->filled('school_id')) {
            $query->whereHas('classSection', fn ($q) => $q->where('school_id', $request->school_id));
        }
        if ($request->filled('session_id')) {
            $query->whereHas('classSection', fn ($q) => $q->where('academic_session_id', $request->session_id));
        }
        if ($request->filled('class_name')) {
            $classNameFilter = trim((string) $request->class_name);
            $query->whereHas('classSection', fn ($q) => $q->where('class_name', $classNameFilter));
        }
        if ($request->input('only_unassigned') === '1') {
            $query->whereNull('assigned_to');
        }
        if ($request->filled('current_assigned_to')) {
            if ($request->current_assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', (int) $request->current_assigned_to);
            }
        }

        $students = $query->paginate(25)->withQueryString();
        $schools = School::orderBy('name')->get();
        $sessions = AcademicSession::orderByDesc('starts_at')->get();
        $classSections = ClassSection::with('school')
            ->when($request->filled('school_id'), fn ($q) => $q->where('school_id', $request->school_id))
            ->when($request->filled('session_id'), fn ($q) => $q->where('academic_session_id', $request->session_id))
            ->orderBy('class_name')
            ->orderBy('section_name')
            ->get();
        $classOptions = $classSections
            ->pluck('class_name')
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $users = User::orderBy('name')->get();
        $telecallers = User::where('is_admin', false)->orderBy('name')->get();

        return view('crm.students.assign', compact('students', 'schools', 'sessions', 'classSections', 'classOptions', 'users', 'telecallers'));
    }

    public function bulkAssign(Request $request)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $data = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $adminId = (int) Auth::id();
        $targetUserId = (int) $data['assigned_to'];
        $now = now();
        $batchUuid = (string) Str::uuid();

        DB::transaction(function () use ($data, $adminId, $targetUserId, $now, $batchUuid) {
            $students = Student::query()
                ->whereIn('id', $data['student_ids'])
                ->lockForUpdate()
                ->select(['id', 'assigned_to'])
                ->get();

            foreach ($students as $student) {
                $fromUserId = (int) ($student->assigned_to ?? 0);

                // If owner actually changes (A -> B), keep transfer timeline.
                if ($fromUserId > 0 && $fromUserId !== $targetUserId) {
                    StudentAssignmentTransfer::create([
                        'student_id' => (int) $student->id,
                        'from_user_id' => $fromUserId,
                        'to_user_id' => $targetUserId,
                        'transferred_by' => $adminId,
                        'transfer_batch_uuid' => $batchUuid,
                        'reason' => 'bulk_assign_owner_change',
                        'transferred_at' => $now,
                    ]);
                }

                Student::whereKey((int) $student->id)->update([
                    'assigned_to' => $targetUserId,
                    'assigned_by' => $adminId,
                    'assigned_at' => $now,
                ]);
            }
        });

        return back()->with('success', __(':count students assigned successfully.', ['count' => count($data['student_ids'])]));
    }

    public function bulkTransfer(Request $request)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $data = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'transfer_to' => 'required|exists:users,id',
            'filter_school_id' => 'nullable|integer',
            'filter_session_id' => 'nullable|integer',
            'filter_class_name' => 'nullable|string|max:100',
            'filter_only_unassigned' => 'nullable|in:0,1',
            'filter_current_assigned_to' => 'nullable|string|max:30',
        ]);

        $scopeQuery = Student::query()->whereIn('id', $data['student_ids']);
        if (! empty($data['filter_school_id'])) {
            $scopeQuery->whereHas('classSection', fn ($q) => $q->where('school_id', (int) $data['filter_school_id']));
        }
        if (! empty($data['filter_session_id'])) {
            $scopeQuery->whereHas('classSection', fn ($q) => $q->where('academic_session_id', (int) $data['filter_session_id']));
        }
        if (! empty($data['filter_class_name'])) {
            $scopeQuery->whereHas('classSection', fn ($q) => $q->where('class_name', trim((string) $data['filter_class_name'])));
        }
        if (($data['filter_only_unassigned'] ?? '0') === '1') {
            $scopeQuery->whereNull('assigned_to');
        }
        if (! empty($data['filter_current_assigned_to'])) {
            if ($data['filter_current_assigned_to'] === 'unassigned') {
                $scopeQuery->whereNull('assigned_to');
            } else {
                $scopeQuery->where('assigned_to', (int) $data['filter_current_assigned_to']);
            }
        }

        $eligibleStudentIds = $scopeQuery->pluck('id')->all();
        if (empty($eligibleStudentIds)) {
            return back()->with('error', __('No eligible students found in the current filter scope.'));
        }

        $data['student_ids'] = $eligibleStudentIds;

        $request->validate([
            'student_ids' => 'required|array|min:1',
        ]);

        $adminId = (int) Auth::id();
        $targetUserId = (int) $data['transfer_to'];
        $batchUuid = (string) Str::uuid();
        $now = now();

        $moved = 0;
        $skipped = 0;

        DB::transaction(function () use ($data, $targetUserId, $adminId, $batchUuid, $now, &$moved, &$skipped) {
            $students = Student::query()->whereIn('id', $data['student_ids'])
                ->lockForUpdate()
                ->select(['id', 'assigned_to'])
                ->get();

            foreach ($students as $student) {
                $fromUserId = (int) ($student->assigned_to ?? 0);
                if ($fromUserId === $targetUserId) {
                    $skipped++;
                    continue;
                }

                StudentAssignmentTransfer::create([
                    'student_id' => (int) $student->id,
                    'from_user_id' => $fromUserId > 0 ? $fromUserId : null,
                    'to_user_id' => $targetUserId,
                    'transferred_by' => $adminId,
                    'transfer_batch_uuid' => $batchUuid,
                    'reason' => null,
                    'transferred_at' => $now,
                ]);

                Student::whereKey((int) $student->id)->update([
                    'assigned_to' => $targetUserId,
                    'assigned_by' => $adminId,
                    'assigned_at' => $now,
                ]);
                $moved++;
            }
        });

        return back()->with('success', __('Redistribution completed. Moved: :moved, Skipped: :skipped.', [
            'moved' => $moved,
            'skipped' => $skipped,
        ]));
    }
}

