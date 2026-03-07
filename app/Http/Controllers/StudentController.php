<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['classSection.school', 'classSection.academicSession'])
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
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('father_name', 'like', "%{$s}%")
                ->orWhere('roll_number', 'like', "%{$s}%")
                ->orWhere('whatsapp_phone_primary', 'like', "%{$s}%"));
        }

        $students = $query->paginate(20)->withQueryString();
        $schools = School::orderBy('name')->get();
        $sessions = AcademicSession::orderByDesc('starts_at')->get();
        $classSections = ClassSection::with('school')->orderBy('class_name')->orderBy('section_name')->get();

        return view('crm.students.index', compact('students', 'schools', 'sessions', 'classSections'));
    }

    public function create()
    {
        $classSections = ClassSection::with('school', 'academicSession')->orderBy('class_name')->orderBy('section_name')->get();

        return view('crm.students.create', compact('classSections'));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'roll_number' => 'nullable|string|max:50',
            'admission_number' => 'nullable|string|max:50',
            'whatsapp_phone_primary' => ['nullable', 'string', 'max:14', function ($attr, $value, $fail) {
                if ($value !== null && $value !== '' && Student::normalizeIndianPhone($value) === null) {
                    $fail(__('Enter a valid 10-digit Indian mobile number (e.g. 9876543210 or +91 9876543210).'));
                }
            }],
            'whatsapp_phone_secondary' => ['nullable', 'string', 'max:14', function ($attr, $value, $fail) {
                if ($value !== null && $value !== '' && Student::normalizeIndianPhone($value) === null) {
                    $fail(__('Enter a valid 10-digit Indian mobile number.'));
                }
            }],
            'status' => 'in:active,inactive',
        ]);
        $valid['status'] = $valid['status'] ?? 'active';

        $valid['whatsapp_phone_primary'] = Student::normalizeIndianPhone($valid['whatsapp_phone_primary'] ?? '') ?: null;
        $valid['whatsapp_phone_secondary'] = Student::normalizeIndianPhone($valid['whatsapp_phone_secondary'] ?? '') ?: null;

        if ($valid['whatsapp_phone_primary'] && Student::isPhoneUsedByOther($valid['whatsapp_phone_primary'])) {
            $existing = Student::findByPhone($valid['whatsapp_phone_primary']);
            return back()->withInput()->withErrors([
                'whatsapp_phone_primary' => __('This number is already used by :name. Use a different number or edit that student.', ['name' => $existing?->name ?? 'another student']),
            ]);
        }
        if ($valid['whatsapp_phone_secondary'] && Student::isPhoneUsedByOther($valid['whatsapp_phone_secondary'])) {
            $existing = Student::findByPhone($valid['whatsapp_phone_secondary']);
            return back()->withInput()->withErrors([
                'whatsapp_phone_secondary' => __('This number is already used by :name.', ['name' => $existing?->name ?? 'another student']),
            ]);
        }

        Student::create($valid);

        return redirect()->route('students.index')->with('success', __('Student added.'));
    }

    public function edit(Student $student)
    {
        $student->load('classSection.school', 'classSection.academicSession');
        $classSections = ClassSection::with('school', 'academicSession')->orderBy('class_name')->orderBy('section_name')->get();

        return view('crm.students.edit', compact('student', 'classSections'));
    }

    public function update(Request $request, Student $student)
    {
        $valid = $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'roll_number' => 'nullable|string|max:50',
            'admission_number' => 'nullable|string|max:50',
            'whatsapp_phone_primary' => ['nullable', 'string', 'max:14', function ($attr, $value, $fail) {
                if ($value !== null && $value !== '' && Student::normalizeIndianPhone($value) === null) {
                    $fail(__('Enter a valid 10-digit Indian mobile number.'));
                }
            }],
            'whatsapp_phone_secondary' => ['nullable', 'string', 'max:14', function ($attr, $value, $fail) {
                if ($value !== null && $value !== '' && Student::normalizeIndianPhone($value) === null) {
                    $fail(__('Enter a valid 10-digit Indian mobile number.'));
                }
            }],
            'status' => 'in:active,inactive',
        ]);

        $valid['whatsapp_phone_primary'] = Student::normalizeIndianPhone($valid['whatsapp_phone_primary'] ?? '') ?: null;
        $valid['whatsapp_phone_secondary'] = Student::normalizeIndianPhone($valid['whatsapp_phone_secondary'] ?? '') ?: null;

        if ($valid['whatsapp_phone_primary'] && Student::isPhoneUsedByOther($valid['whatsapp_phone_primary'], $student->id)) {
            $existing = Student::findByPhone($valid['whatsapp_phone_primary']);
            return back()->withInput()->withErrors([
                'whatsapp_phone_primary' => __('This number is already used by another student.'),
            ]);
        }
        if ($valid['whatsapp_phone_secondary'] && Student::isPhoneUsedByOther($valid['whatsapp_phone_secondary'], $student->id)) {
            return back()->withInput()->withErrors([
                'whatsapp_phone_secondary' => __('This number is already used by another student.'),
            ]);
        }

        $student->update($valid);

        return redirect()->route('students.index')->with('success', __('Student updated.'));
    }
}
