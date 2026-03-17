<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\StudentCall;
use Illuminate\Console\Command;

class BackfillStudentLastCall extends Command
{
    protected $signature = 'app:backfill-student-last-call {--chunk=200 : Number of students to process per chunk}';

    protected $description = 'Rebuild students.last_call_* fields from the latest call that has non-empty notes.';

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk') ?: 200;

        $this->info("Backfilling last call data in chunks of {$chunkSize} students...");

        $count = 0;

        Student::chunkById($chunkSize, function ($students) use (&$count) {
            foreach ($students as $student) {
                /** @var Student $student */
                $lastCallWithNotes = StudentCall::where('student_id', $student->id)
                    ->whereNotNull('call_notes')
                    ->whereRaw("TRIM(call_notes) <> ''")
                    ->orderByDesc('called_at')
                    ->first();

                if (! $lastCallWithNotes) {
                    continue;
                }

                $student->last_call_at = $lastCallWithNotes->called_at;
                $student->last_call_status = $lastCallWithNotes->call_status;
                $student->last_call_notes = $lastCallWithNotes->call_notes;
                $student->save();

                $count++;
            }
        });

        $this->info("Done. Updated {$count} students with last call data.");

        return self::SUCCESS;
    }
}

