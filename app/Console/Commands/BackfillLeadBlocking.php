<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\StudentCall;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class BackfillLeadBlocking extends Command
{
    protected $signature = 'app:backfill-lead-blocking {--chunk=200 : Number of students to process per chunk}';

    protected $description = 'Backfill permanent lead blocking and clear stale follow-ups safely from existing data.';

    private const MAX_NOT_CONNECTED_ATTEMPTS = 3;

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk') ?: 200;
        $now = Carbon::now();

        $this->info("Backfilling lead blocking in chunks of {$chunkSize} students...");

        $scanned = 0;
        $blockedByNotInterested = 0;
        $blockedByAttempts = 0;
        $followupsCleared = 0;
        $updated = 0;

        Student::chunkById($chunkSize, function ($students) use (
            $now,
            &$scanned,
            &$blockedByNotInterested,
            &$blockedByAttempts,
            &$followupsCleared,
            &$updated
        ) {
            foreach ($students as $student) {
                /** @var Student $student */
                $scanned++;
                $dirty = false;

                $isNotInterested = ($student->lead_status === 'not_interested');
                $failedAttempts = StudentCall::where('student_id', $student->id)
                    ->whereIn('call_status', StudentCall::notConnectedStatuses())
                    ->count();

                if ($isNotInterested) {
                    if (! $student->is_call_blocked) {
                        $student->is_call_blocked = true;
                        $dirty = true;
                    }
                    if (($student->blocked_reason ?? '') !== 'not_interested') {
                        $student->blocked_reason = 'not_interested';
                        $dirty = true;
                    }
                    if (! $student->blocked_at) {
                        $student->blocked_at = $now;
                        $dirty = true;
                    }
                    if (! empty($student->next_followup_at)) {
                        $student->next_followup_at = null;
                        $followupsCleared++;
                        $dirty = true;
                    }
                    if ($dirty) {
                        $blockedByNotInterested++;
                    }
                } elseif ($failedAttempts >= self::MAX_NOT_CONNECTED_ATTEMPTS) {
                    if (! $student->is_call_blocked) {
                        $student->is_call_blocked = true;
                        $dirty = true;
                    }
                    if (($student->blocked_reason ?? '') !== 'max_not_connected_attempts') {
                        $student->blocked_reason = 'max_not_connected_attempts';
                        $dirty = true;
                    }
                    if (! $student->blocked_at) {
                        $student->blocked_at = $now;
                        $dirty = true;
                    }
                    if (! empty($student->next_followup_at)) {
                        $student->next_followup_at = null;
                        $followupsCleared++;
                        $dirty = true;
                    }
                    if ($dirty) {
                        $blockedByAttempts++;
                    }
                }

                if ($dirty) {
                    $student->save();
                    $updated++;
                }
            }
        });

        $this->info("Scanned students: {$scanned}");
        $this->info("Updated students: {$updated}");
        $this->info("Blocked by not_interested: {$blockedByNotInterested}");
        $this->info("Blocked by 3+ not-connected attempts: {$blockedByAttempts}");
        $this->info("Cleared stale follow-ups: {$followupsCleared}");
        $this->info('Done.');

        return self::SUCCESS;
    }
}

