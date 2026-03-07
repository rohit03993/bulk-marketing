<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;

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

        return view('crm.phone-campaigns.show', compact('phone', 'displayPhone', 'recipients'));
    }
}
