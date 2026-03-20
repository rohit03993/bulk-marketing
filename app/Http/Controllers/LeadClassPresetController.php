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
            'grade' => 'required|integer|in:1,2,3,4,5,6,7,8,9,10,11,12,13',
            'stream' => ['nullable', 'string', 'max:10', 'regex:/^(NEET|JEE)?$/'],
            'is_active' => 'nullable|boolean',
        ]);

        $stream = trim((string) ($data['stream'] ?? ''));
        // Store "no stream" as empty string so the existing DB schema (non-null column)
        // stays compatible with the unique (grade, stream) constraint.
        if ($stream === '') {
            $stream = '';
        }

        $nextOrder = LeadClassPreset::query()->max('display_order') + 1;
        if (! is_numeric($nextOrder) || $nextOrder < 1) {
            $nextOrder = 1;
        }

        $preset = LeadClassPreset::query()->updateOrCreate(
            ['grade' => (int) $data['grade'], 'stream' => $stream],
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

