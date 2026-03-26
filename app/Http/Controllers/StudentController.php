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
use App\Jobs\RunCampaignJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['classSection.school', 'classSection.academicSession', 'tags'])
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

        // Optional filter by assignee (admin only).
        $currentUser = $request->user();
        $assignedToOptions = [];
        if ($currentUser && $currentUser->isAdmin()) {
            if ($request->filled('assigned_to')) {
                if ($request->assigned_to === 'unassigned') {
                    $query->whereNull('assigned_to');
                } elseif ($request->assigned_to !== 'all') {
                    $query->where('assigned_to', $request->assigned_to);
                }
            }
            $assignedToOptions = \App\Models\User::orderBy('name')->get();
        }

        $students = $query->paginate(20)->withQueryString();
        $schools = School::orderBy('name')->get();
        $sessions = AcademicSession::orderByDesc('starts_at')->get();
        $classSections = ClassSection::with('school')->orderBy('class_name')->orderBy('section_name')->get();

        return view('crm.students.index', compact('students', 'schools', 'sessions', 'classSections', 'assignedToOptions'));
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
            'lead_status' => 'required|in:lead,interested,not_interested,walkin_done,admission_done,follow_up_later',
        ]);
        $valid['status'] = $valid['status'] ?? 'active';
        $valid['lead_status'] = $valid['lead_status'] ?? 'lead';

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
        $schools = School::orderBy('name')->get();

        $assignableUsers = [];
        if (Auth::user()?->isAdmin()) {
            $assignableUsers = \App\Models\User::orderBy('name')->get();
        }

        return view('crm.students.edit', compact('student', 'classSections', 'assignableUsers', 'schools'));
    }

    public function update(Request $request, Student $student)
    {
        $valid = $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'school_id' => 'nullable|exists:schools,id',
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
            'lead_status' => 'required|in:lead,interested,not_interested,walkin_done,admission_done,follow_up_later',
            'assigned_to' => 'nullable|exists:users,id',
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

        // Only admins can change assignment explicitly.
        if (! Auth::user()?->isAdmin()) {
            unset($valid['assigned_to']);
            unset($valid['school_id']);
        } else {
            // Admin-only: allow changing school from profile edit.
            // If selected class belongs to another school, move to same class/section in target school.
            if (! empty($valid['school_id'])) {
                $targetSchoolId = (int) $valid['school_id'];
                $selectedClass = ClassSection::findOrFail((int) $valid['class_section_id']);

                if ((int) $selectedClass->school_id !== $targetSchoolId) {
                    $targetClass = ClassSection::firstOrCreate([
                        'school_id' => $targetSchoolId,
                        'academic_session_id' => (int) $selectedClass->academic_session_id,
                        'class_name' => (string) $selectedClass->class_name,
                        'section_name' => $selectedClass->section_name,
                    ]);
                    $valid['class_section_id'] = (int) $targetClass->id;
                }
            }
        }

        unset($valid['school_id']); // derived through class_section_id only
        $student->update($valid);

        return redirect()->route('students.index')->with('success', __('Student updated.'));
    }

    public function updateLeadStatus(Request $request, Student $student)
    {
        $data = $request->validate([
            'lead_status' => 'required|in:lead,interested,not_interested,walkin_done,admission_done,follow_up_later',
        ]);

        $student->update(['lead_status' => $data['lead_status']]);

        return back()->with('success', __('Lead status updated.'));
    }

    public function show(Request $request, Student $student)
    {
        $student->load([
            'classSection.school',
            'classSection.academicSession',
            'tags',
            'assignedTo',
            'assignedBy',
        ]);

        $calls = StudentCall::where('student_id', $student->id)
            ->with('user')
            ->orderByDesc('called_at')
            ->limit(100)
            ->get();

        $phones = $student->getWhatsappPhones();
        $recipientsQuery = CampaignRecipient::query()
            ->with(['campaign.school', 'campaign.template', 'campaign.shotByUser'])
            ->orderByDesc('created_at');

        $recipientsQuery->where('student_id', $student->id);
        if (! empty($phones)) {
            $recipientsQuery->orWhereIn('phone', $phones);
        }

        $messages = $recipientsQuery->limit(100)->get();

        $templates = AisensyTemplate::orderBy('name')->get();

        return view('crm.students.show', compact('student', 'calls', 'messages', 'phones', 'templates'));
    }

    public function sendSingleMessage(Request $request, Student $student)
    {
        $data = $request->validate([
            'aisensy_template_id' => 'required|exists:aisensy_templates,id',
            'phone' => 'required|string',
        ]);

        $phone = Student::normalizeIndianPhone($data['phone']);
        if (! $phone || ! in_array($phone, $student->getWhatsappPhones(), true)) {
            return back()->with('error', __('Invalid phone number for this student.'));
        }

        $classSection = $student->classSection;
        if (! $classSection || ! $classSection->school_id) {
            return back()->with('error', __('Student is not linked to a school/class; cannot send template message.'));
        }

        $template = AisensyTemplate::findOrFail($data['aisensy_template_id']);

        $campaign = Campaign::create([
            'name' => 'Direct: '.$template->name.' → '.$student->name,
            'school_id' => $classSection->school_id,
            'academic_session_id' => $classSection->academic_session_id,
            'aisensy_template_id' => $template->id,
            'status' => 'queued',
            'total_recipients' => 1,
            'sent_count' => 0,
            'failed_count' => 0,
            'created_by' => $request->user()?->id,
            'shot_by' => $request->user()?->id,
            'shot_at' => now(),
        ]);

        CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'student_id' => $student->id,
            'phone' => $phone,
            'status' => 'pending',
        ]);

        RunCampaignJob::dispatch($campaign->id);

        return back()->with('success', __('Message queued to send for this student.'));
    }

    public function profileEdit(Request $request, Student $student)
    {
        $user = $request->user();
        if (! $user?->isAdmin() && (int) ($student->assigned_to ?? 0) !== (int) ($user?->id ?? 0)) {
            abort(403, __('Access denied.'));
        }

        $student->load('classSection.school', 'classSection.academicSession');
        $classSections = ClassSection::with('school')->orderBy('class_name')->orderBy('section_name')->get();

        return view('crm.students.profile-edit', compact('student', 'classSections'));
    }

    public function profileUpdate(Request $request, Student $student)
    {
        $user = $request->user();
        if (! $user?->isAdmin() && (int) ($student->assigned_to ?? 0) !== (int) ($user?->id ?? 0)) {
            abort(403, __('Access denied.'));
        }

        $valid = $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'roll_number' => 'nullable|string|max:50',
            'admission_number' => 'nullable|string|max:50',
            'whatsapp_phone_primary' => ['nullable', 'string', 'max:14', function ($attr, $value, $fail) use ($student) {
                if ($value !== null && $value !== '' && Student::normalizeIndianPhone($value) === null) {
                    $fail(__('Enter a valid 10-digit Indian mobile number.'));
                }
                $n = Student::normalizeIndianPhone($value ?? '');
                if ($n && Student::isPhoneUsedByOther($n, $student->id)) {
                    $fail(__('This number is already used by another student.'));
                }
            }],
            'whatsapp_phone_secondary' => ['nullable', 'string', 'max:14', function ($attr, $value, $fail) use ($student) {
                if ($value !== null && $value !== '' && Student::normalizeIndianPhone($value) === null) {
                    $fail(__('Enter a valid 10-digit Indian mobile number.'));
                }
                $n = Student::normalizeIndianPhone($value ?? '');
                if ($n && Student::isPhoneUsedByOther($n, $student->id)) {
                    $fail(__('This number is already used by another student.'));
                }
            }],
            'lead_status' => 'required|in:lead,interested,not_interested,walkin_done,admission_done,follow_up_later',
        ]);

        $valid['whatsapp_phone_primary'] = Student::normalizeIndianPhone($valid['whatsapp_phone_primary'] ?? '') ?: null;
        $valid['whatsapp_phone_secondary'] = Student::normalizeIndianPhone($valid['whatsapp_phone_secondary'] ?? '') ?: null;

        $student->update($valid);

        return redirect()->route('students.show', $student)->with('success', __('Student updated.'));
    }
}
