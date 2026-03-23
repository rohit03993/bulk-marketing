<?php

namespace App\Http\Controllers;

use App\Models\AisensyTemplate;
use App\Models\Setting;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function postcallWhatsapp()
    {
        $templates = AisensyTemplate::orderBy('name')->get();

        return view('admin.settings.postcall-whatsapp', [
            'enabled' => (bool) Setting::get('postcall_autosend_enabled', false),
            'templateId' => Setting::get('postcall_autosend_template_id'),
            'campaignBatchSize' => (int) Setting::get('campaign_batch_size', (string) config('campaigns.batch_size', 10)),
            'campaignDelayMinutes' => (int) Setting::get(
                'campaign_next_batch_delay_minutes',
                (string) max(0, (int) round(((int) config('campaigns.next_batch_delay_seconds', 300)) / 60))
            ),
            'templates' => $templates,
        ]);
    }

    public function postcallWhatsappUpdate(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'template_id' => 'nullable|exists:aisensy_templates,id',
            'campaign_batch_size' => 'required|integer|min:1|max:500',
            'campaign_delay_minutes' => 'required|integer|min:0|max:240',
        ]);

        Setting::set('postcall_autosend_enabled', $data['enabled'] ? '1' : '0');
        Setting::set('postcall_autosend_template_id', $data['template_id']);
        Setting::set('campaign_batch_size', (string) $data['campaign_batch_size']);
        Setting::set('campaign_next_batch_delay_minutes', (string) $data['campaign_delay_minutes']);

        return back()->with('success', __('Settings saved.'));
    }
}
