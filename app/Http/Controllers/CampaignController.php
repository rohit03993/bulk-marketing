<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AisensyTemplate;
use App\Models\Campaign;
use App\Jobs\RunCampaignJob;
use App\Models\CampaignRecipient;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::with(['school', 'template'])->orderByDesc('created_at');

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $campaigns = $query->paginate(15)->withQueryString();
        $schools = School::orderBy('name')->get();

        $ids = $campaigns->pluck('id')->all();
        $realCounts = [];
        if (! empty($ids)) {
            $rows = CampaignRecipient::whereIn('campaign_id', $ids)
                ->selectRaw('campaign_id, sum(case when status = ? then 1 else 0 end) as sent_count', ['sent'])
                ->groupBy('campaign_id')
                ->get()
                ->keyBy('campaign_id');
            foreach ($ids as $id) {
                $realCounts[$id] = (int) ($rows->get($id)?->sent_count ?? 0);
            }
        }

        return view('crm.campaigns.index', compact('campaigns', 'schools', 'realCounts'));
    }

    protected function templatePreviewLabel(string $source): string
    {
        if (str_starts_with($source, '"') && str_ends_with($source, '"')) {
            return trim($source, '"');
        }
        $labels = [
            'student.name' => '[Student name]',
            'student.father_name' => '[Father name]',
            'student.roll_number' => '[Roll no.]',
            'student.admission_number' => '[Admission no.]',
            'school.name' => '[School name]',
            'school.short_name' => '[School short name]',
            'class.full_name' => '[Class – Section]',
            'class.name' => '[Class]',
            'class.section' => '[Section]',
            'session.name' => '[Session]',
        ];

        return $labels[$source] ?? '[Param]';
    }

    public function create(Request $request)
    {
        $schools = School::orderBy('name')->get();
        $sessions = AcademicSession::orderByDesc('starts_at')->get();
        $templates = AisensyTemplate::orderBy('name')->get();

        $templatePreviews = [];
        foreach ($templates as $t) {
            $sources = $t->getParamSources();
            $samples = array_map(fn ($s) => $s === null ? '' : $this->templatePreviewLabel($s), $sources);
            $templatePreviews[$t->id] = [
                'body' => $t->body ?? '',
                'samples' => $samples,
            ];
        }

        $schoolId = $request->old('school_id') ?? $request->query('school_id');
        $sessionId = $request->old('academic_session_id') ?? $request->query('session_id');
        $classSectionIds = $request->old('class_section_ids', []);

        $classSections = collect();
        if ($schoolId && $sessionId) {
            $classSections = ClassSection::where('school_id', $schoolId)
                ->where('academic_session_id', $sessionId)
                ->orderBy('class_name')->orderBy('section_name')
                ->get();
        }

        return view('crm.campaigns.create', compact(
            'schools', 'sessions', 'templates', 'templatePreviews', 'classSections',
            'schoolId', 'sessionId', 'classSectionIds'
        ));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'school_id' => 'required|exists:schools,id',
            'academic_session_id' => 'nullable|exists:academic_sessions,id',
            'aisensy_template_id' => 'required|exists:aisensy_templates,id',
            'class_section_ids' => 'required|array',
            'class_section_ids.*' => 'exists:class_sections,id',
        ]);

        $template = AisensyTemplate::findOrFail($valid['aisensy_template_id']);
        $students = Student::whereIn('class_section_id', $valid['class_section_ids'])
            ->where('status', 'active')
            ->with(['classSection.school', 'classSection.academicSession'])
            ->get();

        $recipients = [];
        foreach ($students as $student) {
            $phones = $student->getWhatsappPhones();
            if (empty($phones)) {
                continue;
            }
            foreach ($phones as $phone) {
                $recipients[] = [
                    'student_id' => $student->id,
                    'phone' => $phone,
                ];
            }
        }

        $campaign = Campaign::create([
            'name' => $valid['name'],
            'school_id' => $valid['school_id'],
            'academic_session_id' => $valid['academic_session_id'] ?? null,
            'aisensy_template_id' => $valid['aisensy_template_id'],
            'status' => 'draft',
            'total_recipients' => count($recipients),
            'sent_count' => 0,
            'failed_count' => 0,
            'created_by' => auth()->id(),
        ]);

        foreach ($recipients as $r) {
            CampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'student_id' => $r['student_id'],
                'phone' => $r['phone'],
                'status' => 'pending',
            ]);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', __('Campaign saved with :count recipients. Click "Shoot campaign" to start sending.', ['count' => count($recipients)]));
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['school', 'template', 'shotByUser', 'recipients.student.classSection']);
        $sent = $campaign->recipients()->where('status', 'sent')->count();
        $failed = $campaign->recipients()->where('status', 'failed')->count();
        $pendingCount = $campaign->recipients()->where('status', 'pending')->count();
        if ($sent !== (int) $campaign->sent_count || $failed !== (int) $campaign->failed_count) {
            $campaign->update(['sent_count' => $sent, 'failed_count' => $failed]);
            $campaign->refresh();
        }
        $recipients = $campaign->recipients()->with('student')->paginate(50);

        $campaignBatchSize = max(1, (int) Setting::get('campaign_batch_size', (string) config('campaigns.batch_size', 10)));
        $campaignDelayMinutes = max(
            0,
            (int) Setting::get(
                'campaign_next_batch_delay_minutes',
                (string) max(0, (int) round(((int) config('campaigns.next_batch_delay_seconds', 300)) / 60))
            )
        );
        $showBulkTiming = (int) ($campaign->total_recipients ?? 0) > 1;

        return view('crm.campaigns.show', compact(
            'campaign',
            'recipients',
            'pendingCount',
            'campaignBatchSize',
            'campaignDelayMinutes',
            'showBulkTiming'
        ));
    }

    public function shoot(Campaign $campaign)
    {
        $pending = $campaign->recipients()->where('status', 'pending')->exists();
        if (! $pending) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('info', __('No pending recipients to send.'));
        }
        if ($campaign->status === 'draft') {
            $campaign->update([
                'status' => 'queued',
                'shot_by' => auth()->id(),
                'shot_at' => now(),
            ]);
        } else {
            $campaign->update([
                'shot_by' => auth()->id(),
                'shot_at' => now(),
            ]);
        }
        RunCampaignJob::dispatch($campaign->id);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', __('Sending started. Counts will update in real time.'));
    }

    public function stats(Campaign $campaign)
    {
        $campaign->refresh();
        $sent = $campaign->recipients()->where('status', 'sent')->count();
        $failed = $campaign->recipients()->where('status', 'failed')->count();
        $pending = $campaign->recipients()->where('status', 'pending')->count();
        if ($sent !== (int) $campaign->sent_count || $failed !== (int) $campaign->failed_count) {
            $campaign->update(['sent_count' => $sent, 'failed_count' => $failed]);
        }
        return response()->json([
            'sent_count' => $sent,
            'failed_count' => $failed,
            'pending_count' => $pending,
            'total_recipients' => $campaign->total_recipients,
            'status' => $campaign->status,
        ]);
    }

    public function statsBulk(Request $request)
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids)) {
            $ids = array_filter(explode(',', (string) $ids));
        }
        $ids = array_map('intval', array_slice($ids, 0, 50));
        $campaigns = Campaign::whereIn('id', $ids)->get();
        $realCounts = CampaignRecipient::whereIn('campaign_id', $ids)
            ->selectRaw('campaign_id, sum(case when status = ? then 1 else 0 end) as sent_count, sum(case when status = ? then 1 else 0 end) as failed_count', ['sent', 'failed'])
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');
        $out = [];
        foreach ($campaigns as $c) {
            $rc = $realCounts->get($c->id);
            $sent = $rc ? (int) $rc->sent_count : $c->sent_count;
            $failed = $rc ? (int) $rc->failed_count : $c->failed_count;
            $out[$c->id] = [
                'sent_count' => $sent,
                'failed_count' => $failed,
                'total_recipients' => $c->total_recipients,
                'status' => $c->status,
            ];
        }

        return response()->json($out);
    }
}
