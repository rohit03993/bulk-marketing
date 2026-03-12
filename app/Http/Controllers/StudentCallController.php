<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentCall;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StudentCallController extends Controller
{
    private const LEAD_STATUSES = ['lead', 'interested', 'not_interested', 'walkin_done', 'admission_done', 'follow_up_later'];

    /**
     * Store a new call against a student. Supports both legacy form and Log Call Result wizard.
     */
    public function store(Request $request, Student $student)
    {
        $callDirection = $request->input('call_direction', 'outgoing');
        if (! in_array($callDirection, ['outgoing', 'incoming'], true)) {
            $callDirection = 'outgoing';
        }

        $wizard = $request->has('call_connected');
        if ($wizard) {
            $rules = [
                'call_connected' => 'required|boolean',
                'call_direction' => 'nullable|in:outgoing,incoming',
                'duration_minutes' => 'nullable|integer|min:0|max:600',
                'tags' => 'nullable|array',
                'tags.*' => 'string|in:' . implode(',', array_keys(StudentCall::$quickTags)),
            ];
            if ($request->boolean('call_connected')) {
                $rules['who_answered'] = 'required|in:' . implode(',', array_keys(StudentCall::$whoAnsweredOptions));
                $rules['lead_status'] = 'required|in:' . implode(',', self::LEAD_STATUSES);
                $rules['call_notes'] = 'required|string|min:10|max:2000';
            } else {
                $rules['call_status'] = 'required|in:no_answer,busy,switched_off,not_reachable,wrong_number,callback';
                $rules['call_notes'] = 'nullable|string|max:2000';
            }
            $rules['next_followup_at'] = 'nullable|date';
            $data = $request->validate($rules);

            $callStatus = $request->boolean('call_connected') ? StudentCall::STATUS_CONNECTED : $data['call_status'];
            $newLeadStatus = $request->boolean('call_connected') ? $data['lead_status'] : ($data['call_status'] === 'wrong_number' ? 'not_interested' : 'lead');
            $skipFollowup = in_array($newLeadStatus, ['not_interested', 'admission_done'], true);
            if (! $skipFollowup && empty($data['next_followup_at'])) {
                $request->validate(['next_followup_at' => 'required|date|after:now'], [], ['next_followup_at' => __('Follow-up date')]);
            }
        } else {
            $data = $request->validate([
                'call_status' => 'required|in:connected,no_answer,busy,switched_off,not_reachable,wrong_number,callback',
                'duration_minutes' => 'nullable|integer|min:0|max:600',
                'call_notes' => 'nullable|string|max:2000',
                'lead_status' => 'nullable|in:' . implode(',', self::LEAD_STATUSES),
                'next_followup_at' => 'nullable|date',
            ]);
            $callStatus = $data['call_status'];
            $newLeadStatus = $data['lead_status'] ?? null;
        }

        $user = $request->user();

        $call = new StudentCall();
        $call->student_id = $student->id;
        $call->user_id = $user->id;
        $call->call_status = $callStatus;
        $call->call_direction = $callDirection;
        $call->duration_minutes = (int) ($data['duration_minutes'] ?? 0);
        $call->call_notes = $data['call_notes'] ?? null;
        $call->status_changed_to = $newLeadStatus;
        if ($wizard && $request->boolean('call_connected')) {
            $call->who_answered = $data['who_answered'];
            $call->tags = $request->input('tags', []);
        }
        if (! empty($data['next_followup_at'])) {
            $call->next_followup_at = Carbon::parse($data['next_followup_at']);
        } elseif (! $wizard) {
            $call->next_followup_at = $this->computeNextFollowupAt($callStatus);
        }
        $call->called_at = Carbon::now();
        $call->save();

        $student->total_calls = (int) $student->total_calls + 1;
        $student->last_call_at = $call->called_at;
        $student->last_call_status = $call->call_status;
        $student->last_call_notes = $call->call_notes;
        $student->next_followup_at = $call->next_followup_at;
        if ($newLeadStatus) {
            $student->lead_status = $newLeadStatus;
        }
        if (! $student->assigned_to) {
            $student->assigned_to = $user->id;
            $student->assigned_by = $user->id;
            $student->assigned_at = Carbon::now();
        }
        $student->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Call logged successfully.'),
            ]);
        }

        return back()->with('success', __('Call logged and student updated.'));
    }

    /**
     * Suggest next follow-up date for the Log Call modal (e.g. when Interested / Follow-up later).
     */
    public function suggestFollowup(Request $request)
    {
        $leadStatus = $request->input('lead_status');
        $connected = $request->boolean('call_connected');
        $now = Carbon::now();

        if ($connected && in_array($leadStatus, ['interested', 'follow_up_later'], true)) {
            $suggested = $now->copy()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);
        } elseif (! $connected) {
            $suggested = $now->copy()->addHours(2)->minute(0)->second(0);
        } else {
            $suggested = $now->copy()->addDay()->setHour(10)->setMinute(0)->setSecond(0);
        }

        if ($suggested->lte($now)) {
            $suggested = $now->copy()->addHour()->minute(0)->second(0);
        }
        if ($suggested->hour < 9) {
            $suggested->setHour(9)->setMinute(0);
        } elseif ($suggested->hour >= 20) {
            $suggested->addDay()->setHour(9)->setMinute(0);
        }

        return response()->json([
            'suggested_datetime' => $suggested->format('Y-m-d\TH:i'),
            'suggested_label' => $suggested->isToday()
                ? __('Today') . ', ' . $suggested->format('h:i A')
                : $suggested->format('d M, h:i A'),
        ]);
    }

    protected function computeNextFollowupAt(string $callStatus): ?Carbon
    {
        // Simple safe defaults; we can refine later.
        switch ($callStatus) {
            case StudentCall::STATUS_NO_ANSWER:
            case StudentCall::STATUS_NOT_REACHABLE:
            case StudentCall::STATUS_SWITCHED_OFF:
                return Carbon::now()->addDay();
            case StudentCall::STATUS_BUSY:
                return Carbon::now()->addHours(2);
            case StudentCall::STATUS_CALLBACK:
                return Carbon::now()->addHours(4);
            case StudentCall::STATUS_WRONG_NUMBER:
                return null;
            case StudentCall::STATUS_CONNECTED:
            default:
                return null;
        }
    }
}

