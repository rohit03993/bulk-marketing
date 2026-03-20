<?php

namespace App\Http\Controllers;

use App\Models\LeadClassPreset;
use Illuminate\Http\Request;

class LeadClassPresetController extends Controller
{
    public function index()
    {
        $presets = LeadClassPreset::query()
            ->orderBy('display_order')
            ->orderBy('grade')
            ->orderBy('stream')
            ->get();

        return view('admin.lead-class-presets.index', [
            'presets' => $presets,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'grade' => 'required|integer|in:9,10,11,12,13',
            'stream' => 'required|string|in:NEET,JEE',
            'is_active' => 'nullable|boolean',
        ]);

        $nextOrder = LeadClassPreset::query()->max('display_order') + 1;
        if (! is_numeric($nextOrder) || $nextOrder < 1) {
            $nextOrder = 1;
        }

        $preset = LeadClassPreset::query()->updateOrCreate(
            ['grade' => (int) $data['grade'], 'stream' => $data['stream']],
            [
                'is_active' => (bool) ($data['is_active'] ?? true),
                'display_order' => $nextOrder,
            ]
        );

        return redirect()->back()->with('success', __('Preset saved.'));
    }

    public function toggle(LeadClassPreset $preset, Request $request)
    {
        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $preset->update(['is_active' => (bool) $data['is_active']]);

        return redirect()->back()->with('success', __('Preset updated.'));
    }
}

