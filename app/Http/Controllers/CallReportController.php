<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\StudentCall;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = (bool) ($user?->isAdmin());

        // Default: last 7 days (inclusive)
        $from = $request->input('from') ?: now()->subDays(6)->toDateString();
        $to = $request->input('to') ?: now()->toDateString();

        $connection = $request->input('connection', 'all'); // all|connected|not_connected
        $reason = $request->input('reason'); // specific not-connected status
        $statusChangedTo = $request->input('status_changed_to');
        $search = trim((string) $request->input('search', ''));

        $staffId = $request->input('staff_id');
        $effectiveUserId = $isAdmin && $staffId ? (int) $staffId : (int) $user->id;

        // Keep call reporting aligned with the current fixed academic session.
        $reportSessionId = (int) (
            AcademicSession::where('name', '2025-26')->value('id')
            ?? AcademicSession::where('is_current', true)->value('id')
            ?? AcademicSession::orderByDesc('starts_at')->value('id')
            ?? 0
        );

        $notConnectedStatuses = [
            StudentCall::STATUS_NO_ANSWER,
            StudentCall::STATUS_BUSY,
            StudentCall::STATUS_SWITCHED_OFF,
            StudentCall::STATUS_NOT_REACHABLE,
            StudentCall::STATUS_WRONG_NUMBER,
            StudentCall::STATUS_CALLBACK,
        ];

        $query = StudentCall::query()
            ->with(['student.classSection.school', 'user'])
            ->where('user_id', $effectiveUserId)
            ->whereBetween('called_at', [
                $from.' 00:00:00',
                $to.' 23:59:59',
            ])
            ->orderByDesc('called_at');

        if ($reportSessionId > 0) {
            $query->whereHas('student.classSection', fn ($q) => $q->where('academic_session_id', $reportSessionId));
        }

        if ($connection === 'connected') {
            $query->where('call_status', StudentCall::STATUS_CONNECTED);
        } elseif ($connection === 'not_connected') {
            $query->whereIn('call_status', $notConnectedStatuses);
        }

        if ($reason) {
            $query->where('call_status', $reason);
        }

        if ($statusChangedTo) {
            $query->where('status_changed_to', $statusChangedTo);
        }

        if ($search !== '') {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('whatsapp_phone_primary', 'like', "%{$search}%")
                    ->orWhere('whatsapp_phone_secondary', 'like', "%{$search}%");
            });
        }

        $calls = $query->paginate(10)->withQueryString();

        // Summary counts (same filters, without pagination)
        $base = clone $query;
        $total = (clone $base)->count();
        $connected = (clone $base)->where('call_status', StudentCall::STATUS_CONNECTED)->count();
        $notConnected = (clone $base)->whereIn('call_status', $notConnectedStatuses)->count();

        $summary = [
            'total' => $total,
            'connected' => $connected,
            'not_connected' => $notConnected,
        ];

        // New vs Follow-up breakdown (based on first-ever call per student)
        $scTable = (new StudentCall())->getTable();
        $firstCallSub = DB::table($scTable)
            ->select('student_id', DB::raw('min(id) as first_call_id'))
            ->groupBy('student_id');

        $breakdownQ = DB::table($scTable.' as sc')
            ->joinSub($firstCallSub, 'f', function ($join) {
                $join->on('f.student_id', '=', 'sc.student_id');
            })
            ->join('students as st', 'st.id', '=', 'sc.student_id')
            ->join('class_sections as cs', 'cs.id', '=', 'st.class_section_id')
            ->where('sc.user_id', $effectiveUserId)
            ->whereBetween('sc.called_at', [
                $from.' 00:00:00',
                $to.' 23:59:59',
            ])
            ->whereNull('st.deleted_at');

        if ($reportSessionId > 0) {
            $breakdownQ->where('cs.academic_session_id', '=', $reportSessionId);
        }

        if ($connection === 'connected') {
            $breakdownQ->where('sc.call_status', StudentCall::STATUS_CONNECTED);
        } elseif ($connection === 'not_connected') {
            $breakdownQ->whereIn('sc.call_status', $notConnectedStatuses);
        }

        if ($reason) {
            $breakdownQ->where('sc.call_status', $reason);
        }

        if ($statusChangedTo) {
            $breakdownQ->where('sc.status_changed_to', $statusChangedTo);
        }

        if ($search !== '') {
            $breakdownQ->where(function ($q) use ($search) {
                $q->where('st.name', 'like', "%{$search}%")
                    ->orWhere('st.whatsapp_phone_primary', 'like', "%{$search}%")
                    ->orWhere('st.whatsapp_phone_secondary', 'like', "%{$search}%");
            });
        }

        $breakdownRow = $breakdownQ
            ->selectRaw('sum(case when sc.id = f.first_call_id then 1 else 0 end) as new_calls')
            ->selectRaw('sum(case when sc.id <> f.first_call_id then 1 else 0 end) as followup_calls')
            ->selectRaw('count(*) as total_calls')
            ->first();

        $newCalls = (int) ($breakdownRow?->new_calls ?? 0);
        $followupCalls = (int) ($breakdownRow?->followup_calls ?? 0);

        $summary['new_calls'] = $newCalls;
        $summary['followup_calls'] = $followupCalls;

        $staffOptions = $isAdmin
            ? User::orderBy('name')->get()
            : collect();

        return view('crm.calls.report', [
            'calls' => $calls,
            'summary' => $summary,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'connection' => $connection,
                'reason' => $reason,
                'status_changed_to' => $statusChangedTo,
                'search' => $search,
                'staff_id' => $isAdmin ? $effectiveUserId : null,
            ],
            'staffOptions' => $staffOptions,
            'isAdmin' => $isAdmin,
            'notConnectedStatuses' => $notConnectedStatuses,
        ]);
    }
}

