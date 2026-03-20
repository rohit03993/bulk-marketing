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

    /**
     * Add the standard NEET/JEE class sections (grades 9-13) for a selected school + academic session.
     * Only inserts missing (school_id, academic_session_id, class_name, section_name) combinations.
     */
    public function addNeetJeePreset(Request $request)
    {
        $valid = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'academic_session_id' => 'required|exists:academic_sessions,id',
        ]);

        $presets = [
            ['class_name' => '9', 'section_name' => 'NEET'],
            ['class_name' => '10', 'section_name' => 'NEET'],
            ['class_name' => '11', 'section_name' => 'NEET'],
            ['class_name' => '11', 'section_name' => 'JEE'],
            ['class_name' => '12', 'section_name' => 'NEET'],
            ['class_name' => '12', 'section_name' => 'JEE'],
            ['class_name' => '13', 'section_name' => 'NEET'],
            ['class_name' => '13', 'section_name' => 'JEE'],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($presets as $p) {
            $exists = ClassSection::query()
                ->where('school_id', $valid['school_id'])
                ->where('academic_session_id', $valid['academic_session_id'])
                ->whereRaw('TRIM(class_name) = ?', [trim($p['class_name'])])
                ->whereRaw('UPPER(TRIM(section_name)) = ?', [strtoupper(trim($p['section_name']))])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            ClassSection::create([
                'school_id' => $valid['school_id'],
                'academic_session_id' => $valid['academic_session_id'],
                'class_name' => $p['class_name'],
                'section_name' => $p['section_name'],
            ]);
            $created++;
        }

        return redirect()->back()->with(
            'success',
            __('Preset added. New: :created, already existed: :skipped.', ['created' => $created, 'skipped' => $skipped])
        );
    }
}
