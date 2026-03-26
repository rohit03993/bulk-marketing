<?php

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\Campaign;
use App\Models\ClassSection;
use App\Models\StudentImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeSessionTo202526 extends Command
{
    protected $signature = 'app:normalize-session-2025-26
        {--dry-run : Preview changes only, no DB writes}
        {--set-current : Mark 2025-26 as current session}';

    protected $description = 'Move non-2025-26 session-linked data to 2025-26 safely (class sections, campaigns, imports).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $setCurrent = (bool) $this->option('set-current');

        $targetSession = AcademicSession::where('name', '2025-26')->first();
        if (! $targetSession) {
            $this->error("Session '2025-26' not found. Create it first.");
            return self::FAILURE;
        }

        $targetId = (int) $targetSession->id;
        $this->info('Target session: 2025-26 (id='.$targetId.')');
        $this->info($dryRun ? 'Mode: DRY RUN (no writes).' : 'Mode: APPLY CHANGES.');

        $movedStudents = 0;
        $updatedClassSections = 0;
        $mergedClassSections = 0;
        $updatedCampaigns = 0;
        $updatedImports = 0;

        // Process class sections in non-target sessions.
        $classSections = ClassSection::query()
            ->where('academic_session_id', '!=', $targetId)
            ->orderBy('id')
            ->get();

        foreach ($classSections as $source) {
            /** @var ClassSection $source */
            $target = ClassSection::query()
                ->where('school_id', $source->school_id)
                ->where('academic_session_id', $targetId)
                ->whereRaw('TRIM(class_name) = ?', [trim((string) $source->class_name)])
                ->whereRaw('COALESCE(TRIM(section_name), "") = ?', [trim((string) ($source->section_name ?? ''))])
                ->first();

            if ($target) {
                /** @var ClassSection $target */
                // Merge: move students from old section -> existing 2025-26 section.
                $count = DB::table('students')
                    ->where('class_section_id', $source->id)
                    ->count();

                if (! $dryRun && $count > 0) {
                    DB::table('students')
                        ->where('class_section_id', $source->id)
                        ->update(['class_section_id' => $target->id, 'updated_at' => now()]);
                }

                if (! $dryRun) {
                    // Remove old class section after merge.
                    $remaining = DB::table('students')->where('class_section_id', $source->id)->count();
                    if ($remaining === 0) {
                        $source->delete();
                    }
                }

                $movedStudents += (int) $count;
                $mergedClassSections++;
            } else {
                if (! $dryRun) {
                    $source->academic_session_id = $targetId;
                    $source->save();
                }
                $updatedClassSections++;
            }
        }

        // Campaigns: align all non-target campaigns to 2025-26.
        $campaignsToUpdate = Campaign::query()
            ->whereNotNull('academic_session_id')
            ->where('academic_session_id', '!=', $targetId)
            ->count();
        if (! $dryRun && $campaignsToUpdate > 0) {
            Campaign::query()
                ->whereNotNull('academic_session_id')
                ->where('academic_session_id', '!=', $targetId)
                ->update(['academic_session_id' => $targetId, 'updated_at' => now()]);
        }
        $updatedCampaigns = (int) $campaignsToUpdate;

        // Imports: align old imports to 2025-26 (including null session ids).
        $importsToUpdate = StudentImport::query()
            ->whereNull('academic_session_id')
            ->orWhere('academic_session_id', '!=', $targetId)
            ->count();
        if (! $dryRun && $importsToUpdate > 0) {
            StudentImport::query()
                ->whereNull('academic_session_id')
                ->orWhere('academic_session_id', '!=', $targetId)
                ->update(['academic_session_id' => $targetId, 'updated_at' => now()]);
        }
        $updatedImports = (int) $importsToUpdate;

        if ($setCurrent && ! $dryRun) {
            AcademicSession::query()->update(['is_current' => false]);
            $targetSession->is_current = true;
            $targetSession->save();
        }

        $this->line('---');
        $this->info('Class sections updated to 2025-26: '.$updatedClassSections);
        $this->info('Class sections merged into existing 2025-26: '.$mergedClassSections);
        $this->info('Students moved by class-section merge: '.$movedStudents);
        $this->info('Campaigns updated to 2025-26: '.$updatedCampaigns);
        $this->info('Student imports updated to 2025-26: '.$updatedImports);
        if ($setCurrent) {
            $this->info('Set 2025-26 as current session: '.($dryRun ? 'NO (dry-run)' : 'YES'));
        }
        $this->info($dryRun ? 'Dry-run complete.' : 'Normalization complete.');

        return self::SUCCESS;
    }
}

