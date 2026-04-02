<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentCall;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class TelecallerScoreService
{
    /**
     * Target-based score (0–100) for a period.
     * $dailyTarget: calls per working day (e.g. 25).
     */
    public function compute(int $userId, Carbon $from, Carbon $to, int $dailyTarget = 25): array
    {
        $rows = StudentCall::query()
            ->where('user_id', $userId)
            ->whereBetween('called_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();

        $pendingDueCount = $this->pendingFollowupsDueCount($userId, $to);

        return $this->computeFromRows($rows, $dailyTarget, $pendingDueCount);
    }

    /**
     * Working-days average score from first call date → today.
     * Only days with at least one call are counted.
     */
    public function computeOverallAverage(int $userId, int $dailyTarget = 25): array
    {
        $first = StudentCall::where('user_id', $userId)->min('called_at');
        if (! $first) {
            return ['score' => 0, 'days' => 0, 'from' => null, 'to' => null];
        }

        $from = Carbon::parse($first)->startOfDay();
        $to = now()->endOfDay();

        $rows = StudentCall::query()
            ->where('user_id', $userId)
            ->whereBetween('called_at', [$from, $to])
            ->get();

        // Group by date and compute daily scores only for days with calls.
        $byDay = $rows->groupBy(fn ($r) => Carbon::parse($r->called_at)->toDateString());

        // Follow-up compliance is based on *current pending due/overdue follow-ups*.
        // So "0 pending" => 100%, any pending => lower.
        $pendingDueCount = $this->pendingFollowupsDueCount($userId, now());

        $days = 0;
        $sum = 0;
        foreach ($byDay as $day => $dayRows) {
            $daily = $this->computeFromRows($dayRows, $dailyTarget, $pendingDueCount);
            $sum += $daily['score'];
            $days++;
        }

        // Provide an overall breakdown for UI columns like Calls/Connected%/Follow-up%.
        // Note: this breakdown does not include volume scoring points, so it remains stable
        // even if volume cap logic changes.
        $overallBreakdown = $this->computeFromRows($rows, $dailyTarget, $pendingDueCount)['breakdown'] ?? [];

        return [
            'score' => $days > 0 ? (int) round($sum / $days) : 0,
            'days' => (int) $days,
            'from' => $from->toDateString(),
            'to' => now()->toDateString(),
            'breakdown' => $overallBreakdown,
        ];
    }

    /**
     * Core scoring logic from a collection of StudentCall rows.
     */
    protected function computeFromRows($rows, int $dailyTarget, int $pendingDueCount): array
    {
        $total = $rows->count();
        $connectedRows = $rows->where('call_status', StudentCall::STATUS_CONNECTED);
        $connected = $connectedRows->count();

        $notConnectedStatuses = [
            StudentCall::STATUS_NO_ANSWER,
            StudentCall::STATUS_BUSY,
            StudentCall::STATUS_SWITCHED_OFF,
            StudentCall::STATUS_NOT_REACHABLE,
            StudentCall::STATUS_WRONG_NUMBER,
            StudentCall::STATUS_CALLBACK,
        ];
        $notConnected = $rows->whereIn('call_status', $notConnectedStatuses)->count();

        // Outcome weights (connected calls only).
        // These heavily reward conversion outcomes (walk-in/admission).
        $outcomeWeights = [
            'admission_done' => 1.0,
            'walkin_done' => 0.95,
            'interested' => 0.65,
            'follow_up_later' => 0.6,
            // Keep "Not Interested" deliberately low so we don't encourage those calls.
            'not_interested' => 0.25,
            // Any other/empty connected outcome (incl. "lead" when status_changed_to is not set well).
            'lead' => 0.30,
        ];

        $followupRequired = 0;
        $followupDone = 0;
        $leadInterested = 0;
        $leadWalkin = 0;
        $leadAdmission = 0;
        $leadNotInterested = 0;

        // Notes (call_notes) quality.
        $notesLenSum = 0;
        $notesCount = 0;
        $notesScoreSum = 0.0;
        $notesScoreCount = 0;

        // Engagement (minutes) quality - ONLY for statuses that need follow-up.
        $engagementScoreSum = 0.0;
        $engagementScoreCount = 0;

        // Outcome score sum across connected calls.
        $outcomeScoreSum = 0.0;
        $outcomeScoreCount = 0;
        $outcomeIgnoredCount = 0;

        foreach ($connectedRows as $c) {
            $notes = (string) ($c->call_notes ?? '');
            $trimmedNotes = trim($notes);
            $len = mb_strlen($trimmedNotes);
            if ($len > 0) {
                $notesLenSum += $len;
                $notesCount++;
            }

            $changed = trim((string) ($c->status_changed_to ?? ''));
            if ($changed === 'interested') $leadInterested++;
            if ($changed === 'walkin_done') $leadWalkin++;
            if ($changed === 'admission_done') $leadAdmission++;
            if ($changed === 'not_interested') $leadNotInterested++;

            // Outcome quality per connected call.
            // If status_changed_to is missing/empty/unknown, we IGNORE it in outcome averaging
            // (so the leaderboard % reflects conversions, not incomplete call tagging).
            if ($changed !== '' && array_key_exists($changed, $outcomeWeights)) {
                $outcomeScoreSum += $outcomeWeights[$changed];
                $outcomeScoreCount++;
            } else {
                $outcomeIgnoredCount++;
            }

            if (in_array($changed, ['interested', 'follow_up_later'], true)) {
                $followupRequired++;
                if (! empty($c->next_followup_at)) $followupDone++;
            }

            // Notes quality scoring (connected calls only).
            // 10 chars = near minimum, 130 chars = full. Anything higher is capped.
            $noteScore = $len <= 10 ? 0.0 : min(1.0, ($len - 10) / 120);
            $notesScoreSum += $noteScore;
            $notesScoreCount++;

            // Engagement scoring option B:
            // Minutes count ONLY when the connected outcome is one that needs follow-up.
            if (in_array($changed, ['interested', 'follow_up_later'], true)) {
                $minutes = (int) ($c->duration_minutes ?? 0);
                // 10 minutes or more = full score, diminishing after that.
                $engagementScoreSum += min(1.0, max(0, $minutes) / 10);
                $engagementScoreCount++;
            }
        }

        $connectedRate = $total > 0 ? (int) round(($connected / $total) * 100) : 0;
        $avgNotesLen = $notesCount > 0 ? (int) round($notesLenSum / $notesCount) : 0;
        // Follow-up compliance based on "pending due/overdue reminders".
        // If 0 pending => 100%. If pending exists => drop by 1/(1+pending).
        // Example: pending=1 => 50%, pending=2 => 33%, etc.
        $followupScore = $pendingDueCount <= 0 ? 1.0 : (1.0 / (1 + $pendingDueCount));
        $followupCompliance = (int) round($followupScore * 100);

        // Normalized component scores (0..1 except volume).
        $outcomeScore = $outcomeScoreCount > 0 ? min(1.0, max(0.0, $outcomeScoreSum / $outcomeScoreCount)) : 0.0;
        $notesScore = $notesScoreCount > 0 ? min(1.0, max(0.0, $notesScoreSum / $notesScoreCount)) : 0.0;

        // Engagement score: if there are no follow-up-needed outcomes, keep it neutral (1.0),
        // so we don't unfairly penalize telecallers.
        $engagementScore = $engagementScoreCount > 0
            ? min(1.0, max(0.0, $engagementScoreSum / $engagementScoreCount))
            : 1.0;

        // Note: we intentionally do NOT use followupDone/followupRequired from call history here.
        // You asked to drive this from pending due/overdue reminders.

        // Small activity factor: total_calls vs dailyTarget.
        // NO explicit cap here (so extra calls can still improve the score),
        // but final score is still clamped to 100.
        $volumeRatio = $dailyTarget > 0 ? ($total / $dailyTarget) : 0;

        // Final score (0..100) aligned with rank:
        // Score is driven mainly by total conversions:
        // - Admission is 1 point
        // - Walk-in is 1 point
        // Activity bonus (calls made) is capped to keep conversions as the primary factor.
        $conversionPoints = $leadAdmission + $leadWalkin;
        $conversionMaxPoints = max(1, $dailyTarget); // Conversion-only day => 100%
        $conversionScore = $conversionMaxPoints > 0 ? min(1.0, max(0.0, $conversionPoints / $conversionMaxPoints)) : 0.0;

        // Recognized connected outcomes are used as a proxy for "calls were actually made".
        // This bonus is intentionally tiny so it cannot override conversion ranking.
        $activityBonus = $dailyTarget > 0 ? min(1.0, max(0.0, $outcomeScoreCount / max(1, $dailyTarget))) : 0.0; // 0..1

        // Up to 99 points from conversion; up to 1 point from activity.
        $scoreFloat = ($conversionScore * 99) + ($activityBonus * 1);

        $score = (int) min(100, max(0, round($scoreFloat)));

        return [
            'score' => $score,
            'breakdown' => [
                'daily_target' => $dailyTarget,
                'total_calls' => $total,
                'connected' => $connected,
                'not_connected' => $notConnected,
                'connected_rate' => $connectedRate,
                'avg_notes_len' => $avgNotesLen,
                'followup_required' => $followupRequired,
                'followup_done' => $followupDone,
                'followup_compliance' => $followupCompliance,
                'pending_followups_due' => $pendingDueCount,
                'lead_interested' => $leadInterested,
                'lead_walkin' => $leadWalkin,
                'lead_admission' => $leadAdmission,
                // Conversion-aligned scoring fields for UI transparency.
                'conversion_points' => $conversionPoints,
                'conversion_score_percent' => (int) round($conversionScore * 100),
                'activity_bonus_percent' => (int) round($activityBonus * 100),
                // Kept for potential future detailed reporting.
                'outcome_score' => $outcomeScore,
                'outcome_score_percent' => (int) round($outcomeScore * 100),
                'notes_score' => $notesScore,
                'notes_score_percent' => (int) round($notesScore * 100),
                'engagement_score' => $engagementScore,
                'engagement_score_percent' => (int) round($engagementScore * 100),
                'volume_ratio' => (float) $volumeRatio,
                'outcome_classified_count' => $outcomeScoreCount,
                'outcome_ignored_count' => $outcomeIgnoredCount,
            ],
        ];
    }

    private function pendingFollowupsDueCount(int $userId, Carbon $asOf): int
    {
        // Pending reminders definition:
        // next_followup_at is set and is due/overdue up to the given $asOf.
        $q = Student::query()
            ->where('assigned_to', $userId)
            ->whereIn('lead_status', ['interested', 'follow_up_later'])
            ->whereNotNull('next_followup_at')
            ->where('next_followup_at', '<=', $asOf->copy()->endOfDay());

        // Exclude blocked leads if the field exists.
        if (Schema::hasColumn('students', 'is_call_blocked')) {
            $q->where(function ($qq) {
                $qq->whereNull('is_call_blocked')
                    ->orWhere('is_call_blocked', false)
                    ->orWhere('is_call_blocked', 0);
            });
        }

        return (int) $q->count();
    }
}

