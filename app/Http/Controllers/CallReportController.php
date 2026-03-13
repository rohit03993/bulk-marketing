<?php

namespace App\Http\Controllers;

use App\Models\StudentCall;
use App\Models\User;
use Illuminate\Http\Request;

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

