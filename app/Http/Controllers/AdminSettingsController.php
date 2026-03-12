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
            'templates' => $templates,
        ]);
    }

    public function postcallWhatsappUpdate(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'template_id' => 'nullable|exists:aisensy_templates,id',
        ]);

        Setting::set('postcall_autosend_enabled', $data['enabled'] ? '1' : '0');
        Setting::set('postcall_autosend_template_id', $data['template_id']);

        return back()->with('success', __('Post-call WhatsApp settings saved.'));
    }
}
