<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentCall;
use Illuminate\Http\Request;

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

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'converted') {
                $query->whereIn('lead_status', ['walkin_done', 'admission_done']);
            } else {
                $query->where('lead_status', $request->status);
            }
        }
        if ($request->has('called')) {
            $request->called == '0'
                ? $query->where('total_calls', 0)
                : $query->where('total_calls', '>', 0);
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

        return view('crm.students.my-leads', [
            'students' => $students,
        ]);
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

