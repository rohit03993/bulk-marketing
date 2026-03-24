<?php

namespace App\Http\Controllers;

use App\Jobs\RunCampaignJob;
use App\Models\AisensyTemplate;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentCall;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StudentCallController extends Controller
{
    private const LEAD_STATUSES = ['lead', 'interested', 'not_interested', 'walkin_done', 'admission_done', 'follow_up_later'];

    private const FOLLOWUP_LEAD_STATUSES = ['interested', 'follow_up_later'];

    private const TERMINAL_LEAD_STATUSES = ['not_interested', 'admission_done'];


    private const MAX_NOT_CONNECTED_ATTEMPTS = 3; // permanent cap

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
            $newLeadStatus = $request->boolean('call_connected') ? $data['lead_status'] : ($data['call_status'] === 'wrong_number' ? 'not_interested' : ($student->lead_status ?? 'lead'));

            $skipFollowup = in_array($newLeadStatus, self::TERMINAL_LEAD_STATUSES, true);
            $isNotConnected = in_array($callStatus, StudentCall::notConnectedStatuses(), true);
            $willHitPermanentCap = false;
            if ($isNotConnected) {
                $failedAttemptsSoFar = StudentCall::where('student_id', $student->id)
                    ->whereIn('call_status', StudentCall::notConnectedStatuses())
                    ->count();
                // Include this new call attempt in the cap projection.
                $willHitPermanentCap = ($failedAttemptsSoFar + 1) >= self::MAX_NOT_CONNECTED_ATTEMPTS;
            }

            if (! $skipFollowup && ! $willHitPermanentCap && empty($data['next_followup_at']) && in_array($newLeadStatus, self::FOLLOWUP_LEAD_STATUSES, true)) {
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
        $call->called_at = Carbon::now();

        // Decide next follow-up based on smarter rules.
        $call->next_followup_at = $this->determineNextFollowupAt(
            $student,
            $user,
            $callStatus,
            $newLeadStatus,
            $data,
            $wizard
        );

        $call->save();

        $student->total_calls = (int) $student->total_calls + 1;
        $student->last_call_at = $call->called_at;
        $student->last_call_status = $call->call_status;
        // Preserve previous notes if this call has no notes, so important history is not lost.
        $student->last_call_notes = $call->call_notes !== null && trim($call->call_notes) !== ''
            ? $call->call_notes
            : $student->last_call_notes;
        $student->next_followup_at = $call->next_followup_at;
        if ($newLeadStatus) {
            $student->lead_status = $newLeadStatus;
        }
        if ($newLeadStatus === 'not_interested') {
            $student->next_followup_at = null;
            $this->applyPermanentBlock($student, 'not_interested');
        } elseif ($newLeadStatus === 'admission_done') {
            $student->next_followup_at = null;
        }
        if (in_array($callStatus, StudentCall::notConnectedStatuses(), true)) {
            $failedAttemptsTotal = StudentCall::where('student_id', $student->id)
                ->whereIn('call_status', StudentCall::notConnectedStatuses())
                ->count();
            if ($failedAttemptsTotal >= self::MAX_NOT_CONNECTED_ATTEMPTS) {
                $student->next_followup_at = null;
                $this->applyPermanentBlock($student, 'max_not_connected_attempts');
            }
        }
        if (! $student->assigned_to) {
            $student->assigned_to = $user->id;
            $student->assigned_by = $user->id;
            $student->assigned_at = Carbon::now();
        }
        $student->save();

        if ($callDirection === 'outgoing' && $callStatus === StudentCall::STATUS_CONNECTED) {
            $this->firePostCallWhatsApp($call, $student, $user);
        }

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

    /**
     * Decide the next_followup_at for a new call, without changing existing data.
     *
     * Rules:
     * - Only leads in FOLLOWUP_LEAD_STATUSES get a future follow-up.
     * - Terminal lead statuses never get a follow-up.
     * - Not-connected calls schedule follow-up only up to MAX_NOT_CONNECTED_ATTEMPTS
     *   for this student (permanent cap).
     */
    protected function determineNextFollowupAt(
        Student $student,
        $user,
        string $callStatus,
        ?string $newLeadStatus,
        array $data,
        bool $wizard
    ): ?Carbon {
        $now = Carbon::now();

        // Terminal lead statuses: stop follow-ups.
        if ($newLeadStatus && in_array($newLeadStatus, self::TERMINAL_LEAD_STATUSES, true)) {
            return null;
        }

        // Connected + interested / follow_up_later: honour provided follow-up.
        if ($callStatus === StudentCall::STATUS_CONNECTED && $newLeadStatus && in_array($newLeadStatus, self::FOLLOWUP_LEAD_STATUSES, true)) {
            if (! empty($data['next_followup_at'])) {
                return Carbon::parse($data['next_followup_at']);
            }

            // If somehow missing (non-wizard), fall back to a safe default: tomorrow 10 AM.
            return $now->copy()->addDay()->setHour(10)->setMinute(0)->setSecond(0);
        }

        // Not-connected: only schedule while attempts are under the permanent cap.
        if (in_array($callStatus, StudentCall::notConnectedStatuses(), true)) {
            $failedAttemptsSoFar = StudentCall::where('student_id', $student->id)
                ->whereIn('call_status', StudentCall::notConnectedStatuses())
                ->count();

            // Include this call in projection so the 3rd not-connected call itself gets no follow-up.
            $projectedAttempts = $failedAttemptsSoFar + 1;
            if ($projectedAttempts >= self::MAX_NOT_CONNECTED_ATTEMPTS) {
                // Too many failures already: do not auto-schedule more follow-ups.
                return null;
            }

            if (! empty($data['next_followup_at'])) {
                return Carbon::parse($data['next_followup_at']);
            }

            // Fall back to existing simple defaults.
            return $this->computeNextFollowupAt($callStatus);
        }

        // Legacy / manual path: if non-wizard and they explicitly provided a follow-up, honour it.
        if (! $wizard && ! empty($data['next_followup_at'])) {
            return Carbon::parse($data['next_followup_at']);
        }

        return null;
    }

    protected function firePostCallWhatsApp(StudentCall $call, Student $student, $user): void
    {
        try {
            if (! Setting::get('postcall_autosend_enabled')) {
                return;
            }

            $templateId = Setting::get('postcall_autosend_template_id');
            if (! $templateId) {
                return;
            }

            $template = AisensyTemplate::find($templateId);
            if (! $template) {
                return;
            }

            $studentPhone = $student->whatsapp_phone_primary;
            if (! $studentPhone) {
                $call->update(['whatsapp_auto_status' => 'skipped']);
                return;
            }

            $student->load('classSection.school', 'classSection.academicSession');
            $classSection = $student->classSection;

            $campaign = Campaign::create([
                'name' => $template->name,
                'school_id' => $classSection?->school_id,
                'academic_session_id' => $classSection?->academic_session_id,
                'aisensy_template_id' => $template->id,
                'status' => 'queued',
                'total_recipients' => 1,
                'sent_count' => 0,
                'failed_count' => 0,
                'created_by' => $user->id,
                'shot_by' => $user->id,
                'shot_at' => now(),
            ]);

            CampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'student_id' => $student->id,
                'student_call_id' => $call->id,
                'phone' => $studentPhone,
                'status' => 'pending',
            ]);

            $call->update(['whatsapp_auto_status' => 'queued']);

            RunCampaignJob::dispatch($campaign->id);
        } catch (\Throwable $e) {
            $call->update(['whatsapp_auto_status' => 'failed']);
            \Illuminate\Support\Facades\Log::warning('Post-call WhatsApp failed: ' . $e->getMessage());
        }
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

    protected function applyPermanentBlock(Student $student, string $reason): void
    {
        $student->is_call_blocked = true;
        $student->blocked_reason = $reason;
        if (! $student->blocked_at) {
            $student->blocked_at = Carbon::now();
        }
    }
}

