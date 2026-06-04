<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\AisensyTemplate;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentAssignmentTransfer;
use App\Models\StudentCall;
use App\Models\StudentImport;
use App\Models\StudentImportColumn;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrmDataResetService
{
    /**
     * Remove all CRM entities: schools, sessions, classes, students, campaigns,
     * imports, tags, templates, assignment history, queue rows, and non-admin users.
     * Does not modify settings, lead-class presets, or admin accounts.
     */
    public function wipeAll(?int $actingUserId = null): void
    {
        CampaignRecipient::query()->delete();
        Campaign::query()->delete();
        StudentCall::query()->delete();
        StudentAssignmentTransfer::query()->delete();
        Student::withTrashed()->forceDelete();
        StudentImportColumn::query()->delete();
        StudentImport::query()->delete();
        ClassSection::query()->delete();
        AcademicSession::query()->delete();
        School::query()->delete();
        AisensyTemplate::query()->delete();
        Tag::query()->delete();
        DB::table('student_tag')->delete();

        $schema = DB::getSchemaBuilder();
        if ($schema->hasTable('jobs')) {
            DB::table('jobs')->delete();
        }
        if ($schema->hasTable('job_batches')) {
            DB::table('job_batches')->delete();
        }
        if ($schema->hasTable('failed_jobs')) {
            DB::table('failed_jobs')->delete();
        }

        User::where('is_admin', false)->delete();

        Log::warning('crm_data_reset.wipe_all', [
            'acting_user_id' => $actingUserId,
        ]);
    }

    /**
     * Students in the given schools that block full-school deletion:
     * assigned to a staff member and have at least one logged call.
     *
     * @param  Collection<int,int>|array<int,int>  $schoolIds
     * @return Collection<int, Student>
     */
    public function assignedCalledStudentsInSchools(Collection|array $schoolIds): Collection
    {
        $schoolIds = collect($schoolIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($schoolIds->isEmpty()) {
            return collect();
        }

        $classIds = ClassSection::whereIn('school_id', $schoolIds)->pluck('id');
        if ($classIds->isEmpty()) {
            return collect();
        }

        return Student::withTrashed()
            ->whereIn('class_section_id', $classIds)
            ->whereNotNull('assigned_to')
            ->where(function ($query) {
                $query->where('total_calls', '>', 0)
                    ->orWhereHas('calls');
            })
            ->with(['assignedTo:id,name', 'classSection.school:id,name'])
            ->orderBy('id')
            ->get(['id', 'name', 'assigned_to', 'class_section_id', 'total_calls']);
    }

    /**
     * @param  Collection<int,int>|array<int,int>  $schoolIds
     * @return array<int, array{id: int, name: string, total_students: int, blocking: int, can_delete: bool}>
     */
    public function schoolDeletionPreview(Collection|array $schoolIds): array
    {
        $schoolIds = collect($schoolIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        return School::query()
            ->whereIn('id', $schoolIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (School $school) {
                $classIds = ClassSection::where('school_id', $school->id)->pluck('id');
                $base = Student::withTrashed()->whereIn('class_section_id', $classIds);
                $total = (clone $base)->count();
                $blocking = $this->assignedCalledStudentsInSchools([$school->id])->count();

                return [
                    'id' => (int) $school->id,
                    'name' => $school->name,
                    'total_students' => $total,
                    'blocking' => $blocking,
                    'can_delete' => $blocking === 0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Delete students (and dependents) plus campaign rows that reference them; reconcile campaigns.
     *
     * @param  Collection<int,int>|array<int,int>  $studentIds
     */
    public function deleteStudentsAndHistory(Collection|array $studentIds): void
    {
        $studentIds = collect($studentIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($studentIds->isEmpty()) {
            return;
        }

        $affectedCampaignIds = collect();
        $callIds = StudentCall::whereIn('student_id', $studentIds)->pluck('id');

        if ($callIds->isNotEmpty()) {
            $affectedCampaignIds = $affectedCampaignIds->merge(
                CampaignRecipient::whereIn('student_call_id', $callIds)->pluck('campaign_id')
            );
            CampaignRecipient::whereIn('student_call_id', $callIds)->delete();
            StudentCall::whereIn('id', $callIds)->delete();
        }

        StudentAssignmentTransfer::whereIn('student_id', $studentIds)->delete();

        $affectedCampaignIds = $affectedCampaignIds->merge(
            CampaignRecipient::whereIn('student_id', $studentIds)->pluck('campaign_id')
        );
        CampaignRecipient::whereIn('student_id', $studentIds)->delete();
        DB::table('student_tag')->whereIn('student_id', $studentIds)->delete();
        Student::withTrashed()->whereIn('id', $studentIds)->forceDelete();

        $this->reconcileCampaigns($affectedCampaignIds->filter()->unique()->values());
    }

    /**
     * @param  Collection<int,int>|array<int,int>  $campaignIds
     */
    public function reconcileCampaigns(Collection|array $campaignIds): void
    {
        $campaignIds = collect($campaignIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($campaignIds->isEmpty()) {
            return;
        }

        foreach ($campaignIds as $campaignId) {
            $campaign = Campaign::find($campaignId);
            if (! $campaign) {
                continue;
            }

            $total = (int) CampaignRecipient::where('campaign_id', $campaignId)->count();
            if ($total === 0) {
                $campaign->delete();

                continue;
            }

            $sent = (int) CampaignRecipient::where('campaign_id', $campaignId)->where('status', 'sent')->count();
            $failed = (int) CampaignRecipient::where('campaign_id', $campaignId)->where('status', 'failed')->count();
            $remaining = (int) CampaignRecipient::where('campaign_id', $campaignId)
                ->whereIn('status', ['pending', 'processing'])
                ->count();

            $payload = [
                'total_recipients' => $total,
                'sent_count' => $sent,
                'failed_count' => $failed,
            ];

            if ($remaining === 0) {
                $payload['status'] = 'completed';
                if (! $campaign->finished_at) {
                    $payload['finished_at'] = now();
                }
            }

            $campaign->update($payload);
        }
    }
}
