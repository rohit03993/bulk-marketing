<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\School;
use Illuminate\Http\Request;

class ClassSectionController extends Controller
{
    public function index(Request $request)
    {
        $query = ClassSection::with(['school', 'academicSession'])->orderBy('class_name')->orderBy('section_name');

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('session_id')) {
            $query->where('academic_session_id', $request->session_id);
        }

        $classSections = $query->paginate(15)->withQueryString();
        $schools = School::orderBy('name')->get();
        $sessions = AcademicSession::orderByDesc('starts_at')->get();

        return view('crm.class-sections.index', compact('classSections', 'schools', 'sessions'));
    }

    public function create()
    {
        $schools = School::orderBy('name')->get();
        $sessions = AcademicSession::orderByDesc('starts_at')->get();

        return view('crm.class-sections.create', compact('schools', 'sessions'));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'class_name' => 'required|string|max:50',
            'section_name' => 'nullable|string|max:20',
            'return_to' => ['nullable', 'string', 'max:100'],
        ]);

        unset($valid['return_to']);
        $classSection = ClassSection::create($valid);

        $returnTo = $request->input('return_to');
        if ($returnTo && preg_match('/^students\/(create|\d+\/edit)$/', $returnTo)) {
            $url = url($returnTo);
            $separator = str_contains($url, '?') ? '&' : '?';

            return redirect($url . $separator . 'class_section_id=' . $classSection->id)
                ->with('success', __('Class/Section created. Select it below.'));
        }

        return redirect()->route('class-sections.index')->with('success', __('Class/Section created.'));
    }

    public function edit(ClassSection $classSection)
    {
        $schools = School::orderBy('name')->get();
        $sessions = AcademicSession::orderByDesc('starts_at')->get();

        return view('crm.class-sections.edit', compact('classSection', 'schools', 'sessions'));
    }

    public function update(Request $request, ClassSection $classSection)
    {
        $valid = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'class_name' => 'required|string|max:50',
            'section_name' => 'nullable|string|max:20',
        ]);

        $classSection->update($valid);

        return redirect()->route('class-sections.index')->with('success', __('Class/Section updated.'));
    }
}
