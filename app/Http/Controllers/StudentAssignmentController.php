<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        if ($request->filled('class_section_id')) {
            $query->where('class_section_id', $request->class_section_id);
        }
        if ($request->input('only_unassigned') === '1') {
            $query->whereNull('assigned_to');
        }

        $students = $query->paginate(25)->withQueryString();
        $schools = School::orderBy('name')->get();
        $sessions = AcademicSession::orderByDesc('starts_at')->get();
        $classSections = ClassSection::with('school')->orderBy('class_name')->orderBy('section_name')->get();
        $users = User::orderBy('name')->get();

        return view('crm.students.assign', compact('students', 'schools', 'sessions', 'classSections', 'users'));
    }

    public function bulkAssign(Request $request)
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $data = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'assigned_to' => 'required|exists:users,id',
        ]);

        Student::whereIn('id', $data['student_ids'])->update([
            'assigned_to' => $data['assigned_to'],
            'assigned_by' => Auth::id(),
            'assigned_at' => now(),
        ]);

        return back()->with('success', __(':count students assigned successfully.', ['count' => count($data['student_ids'])]));
    }
}

