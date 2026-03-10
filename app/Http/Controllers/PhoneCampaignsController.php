<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\Student;
use App\Models\StudentCall;
use App\Models\AisensyTemplate;
use App\Models\Campaign;
use App\Jobs\RunCampaignJob;
use Illuminate\Http\Request;

class PhoneCampaignsController extends Controller
{
    /**
     * Show all campaigns (recipient rows) where this phone number was used.
     * Phone is 10-digit Indian (from route constraint).
     */
    public function show(string $phone)
    {
        $recipients = CampaignRecipient::where('phone', $phone)
            ->with(['campaign.template', 'student'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $displayPhone = \App\Models\Student::formatPhoneForDisplay($phone);

        $student = Student::findByPhone($phone);
        if ($student) {
            $student->load('tags');
        }

        $calls = $student
            ? StudentCall::where('student_id', $student->id)
                ->with('user')
                ->orderByDesc('called_at')
                ->limit(20)
                ->get()
            : collect();

        $templates = AisensyTemplate::orderBy('name')->get();

        return view('crm.phone-campaigns.show', compact('phone', 'displayPhone', 'recipients', 'student', 'calls', 'templates'));
    }

    /**
     * Send a single WhatsApp message to this phone using an approved template.
     * Internally creates a one-recipient campaign and queues it.
     */
    public function sendSingle(Request $request, string $phone)
    {
        $student = Student::findByPhone($phone);
        if (! $student) {
            return back()->with('error', __('No student found for this phone number.'));
        }

        $classSection = $student->classSection;
        if (! $classSection || ! $classSection->school_id) {
            return back()->with('error', __('Student is not linked to a school/class; cannot send template message.'));
        }

        $data = $request->validate([
            'aisensy_template_id' => 'required|exists:aisensy_templates,id',
        ]);

        $template = AisensyTemplate::findOrFail($data['aisensy_template_id']);

        $normalizedPhone = Student::normalizeIndianPhone($phone);
        if (! $normalizedPhone) {
            return back()->with('error', __('Invalid phone number.'));
        }

        $campaign = Campaign::create([
            'name' => 'Direct: '.$template->name.' → '.$student->name,
            'school_id' => $classSection->school_id,
            'academic_session_id' => $classSection->academic_session_id,
            'aisensy_template_id' => $template->id,
            'status' => 'queued',
            'total_recipients' => 1,
            'sent_count' => 0,
            'failed_count' => 0,
            'created_by' => $request->user()?->id,
            'shot_by' => $request->user()?->id,
            'shot_at' => now(),
        ]);

        CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'student_id' => $student->id,
            'phone' => $normalizedPhone,
            'status' => 'pending',
        ]);

        RunCampaignJob::dispatch($campaign->id);

        return back()->with('success', __('Message queued to send for this student.'));
    }
}
