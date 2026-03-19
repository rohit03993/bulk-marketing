<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\StudentCall;
use Illuminate\Console\Command;

class BackfillStudentLastCall extends Command
{
    protected $signature = 'app:backfill-student-last-call {--chunk=200 : Number of students to process per chunk}';

    protected $description = 'Rebuild students.last_call_* fields from call history (latest call + latest notes).';

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk') ?: 200;

        $this->info("Backfilling last call data in chunks of {$chunkSize} students...");

        $count = 0;

        Student::chunkById($chunkSize, function ($students) use (&$count) {
            foreach ($students as $student) {
                /** @var Student $student */
                // Latest call (any status, even if no notes) for timestamp + status.
                $lastCall = StudentCall::where('student_id', $student->id)
                    ->orderByDesc('called_at')
                    ->first();

                if (! $lastCall) {
                    continue;
                }

                // Latest call that has non-empty notes, for summary text.
                $lastCallWithNotes = StudentCall::where('student_id', $student->id)
                    ->whereNotNull('call_notes')
                    ->whereRaw("TRIM(call_notes) <> ''")
                    ->orderByDesc('called_at')
                    ->first();

                // Always restore last_call_at + last_call_status from the true latest call.
                $student->last_call_at = $lastCall->called_at;
                $student->last_call_status = $lastCall->call_status;

                // For notes, prefer the latest call that actually has notes (may be older).
                if ($lastCallWithNotes) {
                    $student->last_call_notes = $lastCallWithNotes->call_notes;
                }

                $student->save();

                $count++;
            }
        });

        $this->info("Done. Updated {$count} students with last call data.");

        return self::SUCCESS;
    }
}

