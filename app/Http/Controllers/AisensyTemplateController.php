<?php

namespace App\Http\Controllers;

use App\Models\AisensyTemplate;
use Illuminate\Http\Request;

class AisensyTemplateController extends Controller
{
    public static function paramSourceOptions(): array
    {
        return [
            '' => __('— None —'),
            'student.name' => __('Student name'),
            'student.father_name' => __('Father name'),
            'student.roll_number' => __('Roll number'),
            'school.name' => __('School name'),
            'school.short_name' => __('School short name'),
            'class.full_name' => __('Class (e.g. 6 - A)'),
            'class.name' => __('Class number'),
            'class.section' => __('Section'),
            'session.name' => __('Session name'),
            'caller.name' => __('Caller / Staff name'),
            'caller.phone' => __('Caller / Staff phone'),
        ];
    }

    public function index()
    {
        $templates = AisensyTemplate::orderBy('name')->paginate(15);

        return view('crm.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('crm.templates.create', [
            'paramOptions' => self::paramSourceOptions(),
            'returnTo' => request()->query('return_to'),
        ]);
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:255|unique:aisensy_templates,name',
            'description' => 'nullable|string|max:500',
            'param_count' => 'required|integer|min:0|max:4',
            'body' => 'nullable|string|max:2000',
            'return_to' => ['nullable', 'string', 'max:255'],
        ]);

        $mappings = [];
        for ($i = 0; $i < (int) $valid['param_count']; $i++) {
            $mappings[$i] = $request->input('param_'.$i);
        }
        $valid['param_mappings'] = $mappings;
        unset($valid['return_to']);

        $template = AisensyTemplate::create($valid);

        $returnTo = $request->input('return_to');
        if ($returnTo && preg_match('/^campaigns\/create(\?.*)?$/', $returnTo)) {
            $url = url($returnTo);
            $separator = str_contains($url, '?') ? '&' : '?';

            return redirect($url . $separator . 'aisensy_template_id=' . $template->id)
                ->with('success', __('Template created. Select it below.'));
        }

        return redirect()->route('templates.index')->with('success', __('Template created.'));
    }

    public function edit(AisensyTemplate $template)
    {
        return view('crm.templates.edit', [
            'template' => $template,
            'paramOptions' => self::paramSourceOptions(),
        ]);
    }

    public function update(Request $request, AisensyTemplate $template)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:255|unique:aisensy_templates,name,'.$template->id,
            'description' => 'nullable|string|max:500',
            'param_count' => 'required|integer|min:0|max:4',
            'body' => 'nullable|string|max:2000',
        ]);

        $mappings = [];
        for ($i = 0; $i < (int) $valid['param_count']; $i++) {
            $mappings[$i] = $request->input('param_'.$i);
        }
        $valid['param_mappings'] = $mappings;

        $template->update($valid);

        return redirect()->route('templates.index')->with('success', __('Template updated.'));
    }
}
