<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentCall;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Call Queue (Start Calling): one-by-one lead view for telecallers.
 * Shows today's queue, one lead at a time; Call Now opens dialer then Log Call modal.
 * Excludes students with permanent block or 3+ not-connected attempts.
 */
class CallQueueController extends Controller
{
    private const MAX_NOT_CONNECTED_ATTEMPTS = 3;

    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return redirect()->route('students.index')->with('info', __('Call queue is for staff. Use Students to manage leads.'));
        }

        $queue = $this->getTodayQueue($user);
        $stats = $this->getTodayStats($user);

        return view('crm.students.call-queue', [
            'queue' => $queue,
            'stats' => $stats,
        ]);
    }

    /**
     * AJAX: get next lead after current (for Skip or after logging call).
     */
    public function getNext(Request $request)
    {
        $user = $request->user();
        $currentId = $request->input('current_lead_id');
        $queue = $this->getTodayQueue($user);

        $found = false;
        $next = null;
        foreach ($queue as $student) {
            if ($found) {
                $next = $student;
                break;
            }
            if ((string) $student->id === (string) $currentId) {
                $found = true;
            }
        }

        if (! $next) {
            return response()->json([
                'success' => true,
                'has_next' => false,
                'message' => __('All done for today! Great work!'),
            ]);
        }

        return response()->json([
            'success' => true,
            'has_next' => true,
            'lead' => $this->leadPayload($next),
            'remaining' => $queue->count(),
        ]);
    }

    /**
     * AJAX: get single lead data for updating the current card (e.g. when clicking queue list).
     */
    public function getLeadData(Student $student, Request $request)
    {
        $user = $request->user();
        if ($student->assigned_to !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'lead' => $this->leadPayload($student),
        ]);
    }

    private function getTodayQueue($user): \Illuminate\Support\Collection
    {
        $today = Carbon::today();
        $endOfToday = $today->copy()->endOfDay();
        $now = now();

        $query = Student::with(['classSection.school'])
            ->withCount([
                'calls as not_connected_attempts_count' => function ($q) {
                    $q->whereIn('call_status', StudentCall::notConnectedStatuses());
                },
            ])
            ->where('assigned_to', $user->id)
            ->whereNotIn('lead_status', ['admission_done', 'not_interested'])
            ->where(function ($q) {
                $q->whereNull('is_call_blocked')->orWhere('is_call_blocked', false);
            })
            ->whereNotIn('id', $this->studentIdsExcludedByNotConnectedCap());

        // Never include future follow-ups (tomorrow/next days) in today's queue.
        $query->where(function ($q) use ($endOfToday) {
            $q->whereNull('next_followup_at')->orWhere('next_followup_at', '<=', $endOfToday);
        });

        $query->where(function ($q) use ($today, $now) {
            $q->where('total_calls', 0)
                ->orWhere(function ($q2) use ($now) {
                    $q2->whereNotNull('next_followup_at')->where('next_followup_at', '<', $now);
                })
                ->orWhere(function ($q2) use ($today) {
                    $q2->whereNotNull('next_followup_at')
                        ->whereDate('next_followup_at', $today)
                        ->where(function ($q3) use ($today) {
                            $q3->whereNull('last_call_at')->orWhereDate('last_call_at', '<', $today);
                        });
                })
                ->orWhere(function ($q2) use ($today) {
                    $q2->whereIn('lead_status', ['lead'])
                        ->where(function ($q3) use ($today) {
                            $q3->whereNull('last_call_at')->orWhereDate('last_call_at', '<', $today);
                        });
                });
        });

        $query->orderByRaw("
            CASE
                WHEN next_followup_at IS NOT NULL AND next_followup_at < NOW() THEN 1
                WHEN next_followup_at IS NOT NULL AND DATE(next_followup_at) = ? THEN 2
                WHEN total_calls = 0 THEN 3
                ELSE 4
            END
        ", [$today])
            ->orderBy('next_followup_at')
            ->orderBy('created_at');

        return $query->limit(50)->get();
    }

    /**
     * Student IDs that have 3+ not-connected attempts (global per student).
     * These are excluded permanently from the call queue.
     */
    private function studentIdsExcludedByNotConnectedCap(): \Illuminate\Support\Collection
    {
        return StudentCall::whereIn('call_status', StudentCall::notConnectedStatuses())
            ->select('student_id')
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) >= ?', [self::MAX_NOT_CONNECTED_ATTEMPTS])
            ->pluck('student_id');
    }

    private function getTodayStats($user): array
    {
        $today = Carbon::today();
        $userId = $user->id;

        $callsToday = StudentCall::where('user_id', $userId)->whereDate('called_at', $today)->count();
        $connectedToday = StudentCall::where('user_id', $userId)
            ->whereDate('called_at', $today)
            ->where('call_status', 'connected')
            ->count();
        $pendingFollowups = Student::where('assigned_to', $userId)
            ->whereNotNull('next_followup_at')
            ->where('next_followup_at', '<=', now())
            ->whereNotIn('lead_status', ['admission_done', 'not_interested'])
            ->where(function ($q) {
                $q->whereNull('is_call_blocked')->orWhere('is_call_blocked', false);
            })
            ->count();
        $queueCount = $this->getTodayQueue($user)->count();

        return [
            'calls_today' => $callsToday,
            'connected_today' => $connectedToday,
            'pending_followups' => $pendingFollowups,
            'queue_count' => $queueCount,
        ];
    }

    private function leadPayload(Student $student): array
    {
        $phone = $student->whatsapp_phone_primary ?: $student->whatsapp_phone_secondary;
        $phone = $phone ? preg_replace('/[^0-9]/', '', $phone) : '';
        $phoneDisplay = $phone ? ('+91' . substr($phone, -10)) : '';

        $status = $student->lead_status ?? 'lead';
        $labels = [
            'lead' => __('Uncalled'),
            'interested' => __('Interested'),
            'not_interested' => __('Not Interested'),
            'walkin_done' => __('Walk-in Done'),
            'admission_done' => __('Admission Done'),
            'follow_up_later' => __('Follow-up Later'),
        ];
        $statusLabel = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));

        return [
            'id' => $student->id,
            'name' => $student->name,
            'father_name' => $student->father_name,
            'mobile_number' => $phoneDisplay,
            'phone_raw' => $phone,
            'status' => $status,
            'status_label' => $statusLabel,
            'total_calls' => (int) $student->total_calls,
            'last_call_notes' => $student->last_call_notes,
            'next_followup_at' => $student->next_followup_at?->format('d M, h:i A'),
            'school_class' => $student->classSection ? $student->classSection->full_name . ' (' . ($student->classSection->school?->name ?? '') . ')' : '',
            'is_overdue' => $student->next_followup_at && $student->next_followup_at->isPast(),
            'not_connected_attempts_count' => (int) ($student->not_connected_attempts_count ?? 0),
        ];
    }
}
