<?php

namespace App\Console\Commands;

use App\Jobs\RunCampaignJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Setting;
use App\Models\StudentCall;
use App\Models\User;
use App\Services\AisensyService;
use Illuminate\Console\Command;

class RunCampaign extends Command
{
    protected $signature = 'campaigns:run {campaign} {--batch= : Max recipients to process per run}';

    protected $description = 'Send pending messages for a campaign via Aisensy (batched for large campaigns)';

    public function handle(AisensyService $aisensy): int
    {
        set_time_limit(0);

        $campaignId = (int) $this->argument('campaign');
        $batchSizeFromSettings = (int) Setting::get('campaign_batch_size', (string) config('campaigns.batch_size', 10));
        $batchOption = $this->option('batch');
        $batchSize = is_numeric($batchOption) ? (int) $batchOption : 0;
        $batchSize = $batchSize > 0 ? $batchSize : $batchSizeFromSettings;
        if ($batchSize < 1) {
            $batchSize = 10;
        }

        /** @var Campaign $campaign */
        $campaign = Campaign::with(['template', 'school', 'academicSession', 'recipients.student.classSection.school', 'recipients.student.classSection.academicSession'])
            ->findOrFail($campaignId);

        if ($campaign->status === 'completed') {
            $this->info('Campaign already completed.');

            return self::SUCCESS;
        }
        if ($campaign->status === 'paused') {
            $this->info('Campaign is paused. Skipping run.');

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
        $campaignMedia = null;
        if (! empty($campaign->media_url)) {
            $resolvedMediaUrl = (string) $campaign->media_url;
            if (str_starts_with($resolvedMediaUrl, '/')) {
                $resolvedMediaUrl = url($resolvedMediaUrl);
            }
            $campaignMedia = [
                'url' => $resolvedMediaUrl,
                'filename' => (string) ($campaign->media_filename ?: ('campaign-media-' . $campaign->id)),
            ];
        }

        $shotByUser = $campaign->shot_by ? User::find($campaign->shot_by) : null;

        $pending = CampaignRecipient::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->limit($batchSize)
            ->cursor();

        $sent = 0;
        $failed = 0;
        $processedInBatch = 0;

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

                $templateParams[] = $this->resolveParamSource($source, $student, $classSection, $school, $session, $shotByUser);
            }

            $result = $aisensy->send($recipient->phone, $templateParams, $template->name, $campaignMedia);

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

            if ($recipient->student_call_id) {
                StudentCall::where('id', $recipient->student_call_id)->update([
                    'whatsapp_auto_status' => $recipient->status === 'sent' ? 'success' : 'failed',
                ]);
            }

            $processedInBatch++;
            $this->maybePauseAfterChunk($processedInBatch);
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
            if ($campaign->status === 'paused') {
                $this->info('Campaign paused. Next batch will not be queued.');

                return self::SUCCESS;
            }
            $campaign->status = 'running';
            $campaign->save();

            $delayMinutes = (int) Setting::get(
                'campaign_next_batch_delay_minutes',
                (string) max(0, (int) round(((int) config('campaigns.next_batch_delay_seconds', 0)) / 60))
            );
            $delaySeconds = max(0, $delayMinutes) * 60;
            if ($delaySeconds > 0) {
                RunCampaignJob::dispatch($campaign->id)->delay(now()->addSeconds($delaySeconds));
            } else {
                RunCampaignJob::dispatch($campaign->id);
            }

            $this->info("Campaign still has {$remaining} pending recipients. Next batch queued" . ($delaySeconds > 0 ? " with {$delaySeconds}s delay." : '.'));
        }

        $this->info("Sent: {$sent}, Failed: {$failed}");

        return self::SUCCESS;
    }

    /**
     * Light throttle: pause briefly after every N messages in this batch.
     * Controlled by config/campaigns.php and .env (CAMPAIGN_PAUSE_*).
     */
    protected function maybePauseAfterChunk(int $processedInBatch): void
    {
        $every = (int) config('campaigns.pause_after_messages', 0);
        if ($every < 1) {
            return;
        }

        if ($processedInBatch % $every !== 0) {
            return;
        }

        $seconds = (float) config('campaigns.pause_seconds', 0);
        if ($seconds <= 0) {
            return;
        }

        $micros = (int) round($seconds * 1_000_000);
        if ($micros > 0) {
            usleep($micros);
        }
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

    protected function resolveParamSource(string $source, $student, $classSection, $school, $session, ?User $shotByUser = null): string
    {
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
            'caller.name' => (string) ($shotByUser?->name ?? ''),
            'caller.phone' => (string) ($shotByUser?->phone ?? ''),
            default => '',
        };
    }
}

