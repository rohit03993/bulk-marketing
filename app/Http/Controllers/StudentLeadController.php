<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\LeadClassPreset;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentCall;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentLeadController extends Controller
{
    public function myLeads(Request $request)
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return redirect()->route('students.index')->with('info', __('My Leads is for staff with assigned leads. Use Students to manage all leads.'));
        }

        $query = Student::with(['classSection.school'])
            ->where('assigned_to', $user->id)
            ->orderBy('next_followup_at')
            ->orderBy('name');

        // Telecaller should be able to view only the leads they personally added.
        if ($request->boolean('added_by_me')) {
            $query->where('assigned_by', $user->id);
        }

        if ($request->filled('school_id')) {
            $schoolId = (int) $request->input('school_id');
            if ($schoolId > 0) {
                $query->whereHas('classSection', fn ($q) => $q->where('school_id', $schoolId));
            }
        }

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'converted') {
                $query->whereIn('lead_status', ['walkin_done', 'admission_done']);
            } else {
                $query->where('lead_status', $request->status);
            }
        }
        // "Called" dropdown uses empty value for "Any".
        // Use filled() so "Any" does not accidentally filter.
        if ($request->filled('called')) {
            $called = (string) $request->input('called');
            if ($called === '0') {
                $query->where('total_calls', 0);
            } else {
                $query->where('total_calls', '>', 0);
            }
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('father_name', 'like', "%{$s}%")
                    ->orWhere('whatsapp_phone_primary', 'like', "%{$s}%");
            });
        }

        $students = $query->paginate(15)->withQueryString();

        $classPresets = LeadClassPreset::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('grade')
            ->orderBy('stream')
            ->get(['id', 'grade', 'stream', 'display_order']);

        $schools = School::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('crm.students.my-leads', [
            'students' => $students,
            'classPresets' => $classPresets,
            'schools' => $schools,
        ]);
    }

    /**
     * Tellcaller adds a new lead under a selected admin-defined category tag.
     * - New student is always created as "Uncalled" (lead_status = lead, total_calls starts at 0).
     * - Student is assigned to the current tellcaller so it appears in "My Leads" and "Start Calling".
     */
    public function addLead(Request $request)
    {
        $user = $request->user();
        abort_if($user->isAdmin(), 404);

        $schoolChoice = (string) $request->input('school_id');
        $isNotInList = $schoolChoice === 'not_in_list';

        $data = $request->validate([
            'class_preset_id' => [
                'required',
                'integer',
                Rule::exists('lead_class_presets', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'school_id' => ['required', 'string', 'max:20'],
            'new_school_name' => ['nullable', 'string', 'max:255', 'required_if:school_id,not_in_list'],
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            // Strict: must be exactly 10 digits; +91 is shown in UI but we store only digits.
            'whatsapp_phone_primary' => ['required', 'regex:/^\\d{10}$/'],
            // Secondary is optional; allow empty string from the form input.
            'whatsapp_phone_secondary' => ['nullable', 'regex:/^$|^\\d{10}$/'],
        ]);

        $primaryTen = $data['whatsapp_phone_primary'];
        $secondaryTen = ! empty($data['whatsapp_phone_secondary']) ? $data['whatsapp_phone_secondary'] : null;

        if (Student::isPhoneUsedByOther($primaryTen)) {
            $existing = Student::findByPhone($primaryTen);
            return back()->withInput()->withErrors([
                'whatsapp_phone_primary' => __('This number is already used by :name. Use a different number or edit that student.', ['name' => $existing?->name ?? 'another student']),
            ]);
        }

        if ($secondaryTen && Student::isPhoneUsedByOther($secondaryTen)) {
            $existing = Student::findByPhone($secondaryTen);
            return back()->withInput()->withErrors([
                'whatsapp_phone_secondary' => __('This number is already used by :name. Use a different number or edit that student.', ['name' => $existing?->name ?? 'another student']),
            ]);
        }

        $preset = LeadClassPreset::query()
            ->where('id', (int) $data['class_preset_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $currentSessionId = AcademicSession::query()
            ->where('is_current', true)
            ->value('id')
            ?? AcademicSession::query()->orderByDesc('starts_at')->value('id');

        if (! $currentSessionId) {
            return back()->withErrors(['academic_session' => __('No academic session found. Create one first.')])->withInput();
        }

        // Resolve school (existing vs "not in list" -> manual create).
        if ($isNotInList) {
            $schoolName = trim((string) $data['new_school_name']);
            if ($schoolName === '') {
                return back()->withErrors(['new_school_name' => __('School name is required.')])->withInput();
            }
            $school = School::query()->firstOrCreate(['name' => $schoolName]);
        } else {
            $school = School::query()->findOrFail((int) $data['school_id']);
        }

        $className = (string) $preset->grade;
        $rawStream = strtoupper(trim((string) $preset->stream));
        // When preset has "no stream", create/lookup a ClassSection with section_name = NULL.
        $sectionName = $rawStream === '' ? null : $rawStream;

        $classSection = ClassSection::query()->firstOrCreate([
            'school_id' => $school->id,
            'academic_session_id' => (int) $currentSessionId,
            'class_name' => $className,
            'section_name' => $sectionName,
        ]);

        $student = Student::create([
            'class_section_id' => (int) $classSection->id,
            'name' => $data['name'],
            'father_name' => $data['father_name'] ?? null,
            'whatsapp_phone_primary' => $primaryTen,
            'whatsapp_phone_secondary' => $secondaryTen,
            'status' => 'active',
            // New lead must show as "Uncalled" in UI.
            'lead_status' => 'lead',

            // Assign directly to the tellcaller so it shows in My Leads / Call Queue.
            'assigned_to' => $user->id,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
        ]);

        return redirect()->route('students.my-leads')->with('success', __('Lead added to your My Leads.'));
    }

    public function followups(Request $request)
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return redirect()->route('students.index')->with('info', __('Follow-ups is for staff with assigned leads. Use Students to manage all leads.'));
        }

        $now = now();
        $endOfToday = $now->copy()->endOfDay();
        $upcomingDays = 14; // show upcoming follow-ups for the next N days
        $upcomingUntil = $endOfToday->copy()->addDays($upcomingDays);

        // Only these lead statuses participate in the follow-up loop.
        $followupLeadStatuses = ['interested', 'follow_up_later'];

        $notConnectedStatuses = [
            StudentCall::STATUS_NO_ANSWER,
            StudentCall::STATUS_BUSY,
            StudentCall::STATUS_SWITCHED_OFF,
            StudentCall::STATUS_NOT_REACHABLE,
            StudentCall::STATUS_WRONG_NUMBER,
            StudentCall::STATUS_CALLBACK,
        ];

        // Not connected today (separate list)
        $notConnectedToday = Student::with(['classSection.school'])
            ->where('assigned_to', $user->id)
            ->where(function ($q) {
                $q->whereNull('is_call_blocked')->orWhere('is_call_blocked', false);
            })
            ->whereDate('last_call_at', $now->toDateString())
            ->whereIn('last_call_status', $notConnectedStatuses)
            ->orderByDesc('last_call_at')
            ->orderBy('name')
            ->take(50)
            ->get();
        $notConnectedIds = $notConnectedToday->pluck('id')->all();

        // Due or overdue follow-ups (today and earlier)
        $dueQuery = Student::with(['classSection.school'])
            ->where('assigned_to', $user->id)
            ->where(function ($q) {
                $q->whereNull('is_call_blocked')->orWhere('is_call_blocked', false);
            })
            ->whereIn('lead_status', $followupLeadStatuses)
            ->whereNotNull('next_followup_at')
            ->where('next_followup_at', '<=', $endOfToday)
            ->when(! empty($notConnectedIds), fn ($q) => $q->whereNotIn('id', $notConnectedIds))
            ->orderBy('next_followup_at')
            ->orderBy('name');

        if ($request->filled('status') && $request->status !== 'all') {
            // Allow narrowing within the follow-up statuses only.
            if (in_array($request->status, $followupLeadStatuses, true)) {
                $dueQuery->where('lead_status', $request->status);
            }
        }

        $dueStudents = $dueQuery->paginate(15)->withQueryString();

        // Upcoming follow-ups in the next N days (excluding today)
        $upcomingQuery = Student::with(['classSection.school'])
            ->where('assigned_to', $user->id)
            ->where(function ($q) {
                $q->whereNull('is_call_blocked')->orWhere('is_call_blocked', false);
            })
            ->whereIn('lead_status', $followupLeadStatuses)
            ->whereNotNull('next_followup_at')
            ->whereBetween('next_followup_at', [$endOfToday->copy()->addSecond(), $upcomingUntil])
            ->when(! empty($notConnectedIds), fn ($q) => $q->whereNotIn('id', $notConnectedIds))
            ->orderBy('next_followup_at')
            ->orderBy('name');

        if ($request->filled('status') && $request->status !== 'all') {
            if (in_array($request->status, $followupLeadStatuses, true)) {
                $upcomingQuery->where('lead_status', $request->status);
            }
        }

        $upcomingStudents = $upcomingQuery->take(50)->get();

        return view('crm.students.followups', [
            'notConnectedToday' => $notConnectedToday,
            'dueStudents' => $dueStudents,
            'upcomingStudents' => $upcomingStudents,
            'upcomingDays' => $upcomingDays,
        ]);
    }
}

