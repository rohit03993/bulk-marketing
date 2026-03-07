<?php

namespace App\Console\Commands;

use App\Jobs\RunCampaignJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Services\AisensyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunCampaign extends Command
{
    protected $signature = 'campaigns:run {campaign} {--batch=200 : Max recipients to process per run}';

    protected $description = 'Send pending messages for a campaign via Aisensy (batched for large campaigns)';

    public function handle(AisensyService $aisensy): int
    {
        set_time_limit(0);

        $campaignId = (int) $this->argument('campaign');
        $batchSize = (int) $this->option('batch');
        $batchSize = $batchSize > 0 ? $batchSize : 200;

        /** @var Campaign $campaign */
        $campaign = Campaign::with(['template', 'school', 'academicSession', 'recipients.student.classSection.school', 'recipients.student.classSection.academicSession'])
            ->findOrFail($campaignId);

        if ($campaign->status === 'completed') {
            $this->info('Campaign already completed.');

            return self::SUCCESS;
        }

        if ($campaign->status === 'draft') {
            $campaign->status = 'queued';
        }

        if (! $campaign->started_at) {
            $campaign->started_at = now();
        }

        $campaign->save();

        $template = $campaign->template;
        $paramSources = $template->getParamSources();

        $pending = CampaignRecipient::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->limit($batchSize)
            ->cursor();

        $sent = 0;
        $failed = 0;

        foreach ($pending as $recipient) {
            /** @var CampaignRecipient $recipient */
            $recipient->load(['student.classSection.school', 'student.classSection.academicSession']);
            $student = $recipient->student;
            $classSection = $student?->classSection;
            $school = $classSection?->school;
            $session = $classSection?->academicSession;

            $templateParams = [];

            foreach ($paramSources as $source) {
                if ($source === null) {
                    $templateParams[] = '';

                    continue;
                }

                $templateParams[] = $this->resolveParamSource($source, $student, $classSection, $school, $session);
            }

            $result = $aisensy->send($recipient->phone, $templateParams, $template->name);

            $recipient->template_params = $templateParams;
            $recipient->message_sent = $this->buildMessageSent($template->body, $templateParams);

            if ($result['status'] === 'success') {
                $recipient->status = 'sent';
                $recipient->provider_response = $result['response'] ?? null;
                $recipient->error_message = null;
                $sent++;
            } else {
                $recipient->status = 'failed';
                $recipient->provider_response = $result['response'] ?? null;
                $recipient->error_message = $result['error'] ?? 'Unknown error';
                $failed++;
            }

            $recipient->save();
        }

        $sentCount = CampaignRecipient::where('campaign_id', $campaign->id)->where('status', 'sent')->count();
        $failedCount = CampaignRecipient::where('campaign_id', $campaign->id)->where('status', 'failed')->count();
        $campaign->update(['sent_count' => $sentCount, 'failed_count' => $failedCount]);

        $remaining = CampaignRecipient::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->count();

        if ($remaining === 0) {
            $campaign->refresh();
            $campaign->status = 'completed';
            $campaign->finished_at = now();
            $campaign->save();

            $this->info('Campaign completed.');
        } else {
            $campaign->refresh();
            $campaign->status = 'running';
            $campaign->save();

            RunCampaignJob::dispatch($campaign->id);

            $this->info("Campaign still has {$remaining} pending recipients. Next batch queued.");
        }

        $this->info("Sent: {$sent}, Failed: {$failed}");

        return self::SUCCESS;
    }

    protected function buildMessageSent(?string $body, array $templateParams): ?string
    {
        if ($body === null || $body === '') {
            return null;
        }
        $message = $body;
        foreach ($templateParams as $i => $value) {
            $placeholder = '{{'.($i + 1).'}}';
            $message = str_replace($placeholder, (string) $value, $message);
        }

        return $message;
    }

    protected function resolveParamSource(string $source, $student, $classSection, $school, $session): string
    {
        // Static text in quotes: "Dear Parent"
        if (str_starts_with($source, '"') && str_ends_with($source, '"')) {
            return trim($source, '"');
        }

        return match ($source) {
            'student.name' => (string) ($student->name ?? ''),
            'student.father_name' => (string) ($student->father_name ?? ''),
            'student.roll_number' => (string) ($student->roll_number ?? ''),
            'student.admission_number' => (string) ($student->admission_number ?? ''),
            'school.name' => (string) ($school->name ?? ''),
            'school.short_name' => (string) ($school->short_name ?? ''),
            'class.full_name' => (string) ($classSection?->full_name ?? ''),
            'class.name' => (string) ($classSection->class_name ?? ''),
            'class.section' => (string) ($classSection->section_name ?? ''),
            'session.name' => (string) ($session->name ?? ''),
            default => '',
        };
    }
}

