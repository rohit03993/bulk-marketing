<?php

namespace App\Services;

use App\Models\StudentCall;
use Carbon\Carbon;

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

        return $this->computeFromRows($rows, $dailyTarget);
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

        $days = 0;
        $sum = 0;
        foreach ($byDay as $day => $dayRows) {
            $daily = $this->computeFromRows($dayRows, $dailyTarget);
            $sum += $daily['score'];
            $days++;
        }

        return [
            'score' => $days > 0 ? (int) round($sum / $days) : 0,
            'days' => (int) $days,
            'from' => $from->toDateString(),
            'to' => now()->toDateString(),
        ];
    }

    /**
     * Core scoring logic from a collection of StudentCall rows.
     */
    protected function computeFromRows($rows, int $dailyTarget): array
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

        $followupRequired = 0;
        $followupDone = 0;
        $leadInterested = 0;
        $leadWalkin = 0;
        $leadAdmission = 0;
        $leadNotInterested = 0;
        $notesLenSum = 0;
        $notesCount = 0;

        foreach ($connectedRows as $c) {
            $notes = (string) ($c->call_notes ?? '');
            $len = mb_strlen(trim($notes));
            if ($len > 0) {
                $notesLenSum += $len;
                $notesCount++;
            }

            $changed = (string) ($c->status_changed_to ?? '');
            if ($changed === 'interested') $leadInterested++;
            if ($changed === 'walkin_done') $leadWalkin++;
            if ($changed === 'admission_done') $leadAdmission++;
            if ($changed === 'not_interested') $leadNotInterested++;

            if (in_array($changed, ['interested', 'follow_up_later'], true)) {
                $followupRequired++;
                if (! empty($c->next_followup_at)) $followupDone++;
            }
        }

        $connectedRate = $total > 0 ? (int) round(($connected / $total) * 100) : 0;
        $avgNotesLen = $notesCount > 0 ? (int) round($notesLenSum / $notesCount) : 0;
        $followupCompliance = $followupRequired > 0 ? (int) round(($followupDone / $followupRequired) * 100) : 100;

        // Volume: up to 50 points for hitting dailyTarget calls.
        $volumeRatio = $dailyTarget > 0 ? min(1, $total / $dailyTarget) : 0;
        $volumePoints = (int) round($volumeRatio * 50);

        // Quality: up to 50 points.
        $connectedRate01 = $total > 0 ? ($connected / $total) : 0.0;
        $followup01 = $followupRequired > 0 ? ($followupDone / $followupRequired) : 1.0;
        $avgNotes01 = $avgNotesLen > 0 ? min(1.0, $avgNotesLen / 80) : 0.0;

        $connectedDen = max(1, $connected);
        $outcomeRawPerConnected =
            (($leadInterested * 3) + ($leadWalkin * 5) + ($leadAdmission * 8) + ($leadNotInterested * 1))
            / (8 * $connectedDen);
        $outcome01 = min(1.0, $outcomeRawPerConnected);

        $quality01 = (0.35 * $connectedRate01) + (0.25 * $followup01) + (0.25 * $avgNotes01) + (0.15 * $outcome01);
        $qualityPoints = $total > 0 ? (int) round($quality01 * 50) : 0;

        $score = min(100, $volumePoints + $qualityPoints);

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
                'lead_interested' => $leadInterested,
                'lead_walkin' => $leadWalkin,
                'lead_admission' => $leadAdmission,
            ],
        ];
    }
}

