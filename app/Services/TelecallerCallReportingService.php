<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentCall;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TelecallerCallReportingService
{
    private const MAX_NOT_CONNECTED_ATTEMPTS = 3;

    public function pendingCallsCounts(array $telecallerIds, int $sessionId, ?Carbon $now = null): array
    {
        if (empty($telecallerIds)) {
            return [];
        }

        $now = $now ? $now->copy() : now();
        $now = $now->setTimezone('Asia/Kolkata');
        $today = $now->toDateString();
        $endOfToday = $now->copy()->endOfDay();

        $hasBlockField = Schema::hasColumn('students', 'is_call_blocked');

        $excludedIds = $this->studentIdsExcludedByNotConnectedCap();

        $pendingByTelecaller = [];
        foreach ($telecallerIds as $telecallerId) {
            $base = Student::query()
                ->where('assigned_to', (int) $telecallerId)
                ->whereNotIn('lead_status', ['admission_done', 'not_interested'])
                ->whereHas('classSection', fn ($q) => $q->where('academic_session_id', $sessionId))
                ->where(function ($q) use ($endOfToday) {
                    // Never include future follow-ups.
                    $q->whereNull('next_followup_at')
                        ->orWhere('next_followup_at', '<=', $endOfToday);
                });

            if ($hasBlockField) {
                $base->where(function ($q) {
                    $q->whereNull('is_call_blocked')
                        ->orWhere('is_call_blocked', false);
                });
            }

            if ($excludedIds->isNotEmpty()) {
                $base->whereNotIn('id', $excludedIds);
            }

            // Exact queue "pending" rules (today queue logic), but without the 50 limit.
            $base->where(function ($q) use ($today, $now) {
                $q->where('total_calls', 0)
                    ->orWhere(function ($q2) use ($now) {
                        $q2->whereNotNull('next_followup_at')
                            ->where('next_followup_at', '<', $now);
                    })
                    ->orWhere(function ($q2) use ($today) {
                        $q2->whereNotNull('next_followup_at')
                            ->whereDate('next_followup_at', $today)
                            ->where(function ($q3) use ($today) {
                                $q3->whereNull('last_call_at')
                                    ->orWhereDate('last_call_at', '<', $today);
                            });
                    })
                    ->orWhere(function ($q2) use ($today) {
                        $q2->whereIn('lead_status', ['lead'])
                            ->where(function ($q3) use ($today) {
                                $q3->whereNull('last_call_at')
                                    ->orWhereDate('last_call_at', '<', $today);
                            });
                    });
            });

            $pendingTotal = (int) $base->count();
            $pendingNew = (int) (clone $base)->where('total_calls', 0)->count();
            $pendingFollowup = max(0, $pendingTotal - $pendingNew);

            $pendingByTelecaller[$telecallerId] = [
                'pending_total' => $pendingTotal,
                'pending_new' => $pendingNew,
                'pending_followup' => $pendingFollowup,
            ];
        }

        return $pendingByTelecaller;
    }

    public function dailyCallsSplit(
        array $telecallerIds,
        int $sessionId,
        Carbon $from,
        Carbon $to
    ): array {
        if (empty($telecallerIds)) {
            return [];
        }

        $from = $from->copy()->setTimezone('Asia/Kolkata')->startOfDay();
        $to = $to->copy()->setTimezone('Asia/Kolkata')->endOfDay();

        $scTable = (new StudentCall())->getTable(); // student_calls

        // first_call_ids = min(call id) per student, so "new call" is where sc.id == first_call_id
        $firstCallSub = DB::table($scTable)
            ->select('student_id', DB::raw('min(id) as first_call_id'))
            ->groupBy('student_id');

        $rows = DB::table($scTable . ' as sc')
            ->joinSub($firstCallSub, 'f', function ($join) {
                $join->on('f.student_id', '=', 'sc.student_id');
            })
            ->join('students as st', 'st.id', '=', 'sc.student_id')
            ->join('class_sections as cs', 'cs.id', '=', 'st.class_section_id')
            ->whereIn('sc.user_id', array_map('intval', $telecallerIds))
            ->whereBetween('sc.called_at', [$from, $to])
            ->where('cs.academic_session_id', '=', $sessionId)
            ->whereNull('st.deleted_at')
            ->selectRaw('DATE(sc.called_at) as called_date')
            ->selectRaw('sc.user_id as telecaller_id')
            ->selectRaw('sum(case when sc.id = f.first_call_id then 1 else 0 end) as new_calls')
            ->selectRaw('sum(case when sc.id <> f.first_call_id then 1 else 0 end) as followup_calls')
            ->selectRaw('count(*) as total_calls')
            ->groupBy(DB::raw('DATE(sc.called_at)'), 'sc.user_id')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $date = (string) $r->called_date;
            $tid = (int) $r->telecaller_id;
            $out[$date][$tid] = [
                'new_calls' => (int) $r->new_calls,
                'followup_calls' => (int) $r->followup_calls,
                'total_calls' => (int) $r->total_calls,
            ];
        }

        return $out;
    }

    private function studentIdsExcludedByNotConnectedCap(): Collection
    {
        return StudentCall::query()
            ->whereIn('call_status', StudentCall::notConnectedStatuses())
            ->select('student_id')
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) >= ?', [self::MAX_NOT_CONNECTED_ATTEMPTS])
            ->pluck('student_id');
    }
}

