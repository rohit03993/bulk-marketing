<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use Illuminate\Http\Request;

class AcademicSessionController extends Controller
{
    public function index()
    {
        $sessions = AcademicSession::orderByDesc('starts_at')->paginate(15);

        return view('crm.sessions.index', compact('sessions'));
    }

    public function create()
    {
        return view('crm.sessions.create');
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:100',
            'is_current' => 'boolean',
        ]);
        $valid['is_current'] = $request->boolean('is_current');
        $valid['starts_at'] = null;
        $valid['ends_at'] = null;

        if ($valid['is_current'] ?? false) {
            AcademicSession::query()->update(['is_current' => false]);
        }

        AcademicSession::create($valid);

        return redirect()->route('sessions.index')->with('success', __('Session created.'));
    }

    public function edit(AcademicSession $academic_session)
    {
        return view('crm.sessions.edit', ['session' => $academic_session]);
    }

    public function update(Request $request, AcademicSession $academic_session)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:100',
            'is_current' => 'boolean',
        ]);
        $valid['is_current'] = $request->boolean('is_current');
        $valid['starts_at'] = null;
        $valid['ends_at'] = null;

        if ($valid['is_current'] ?? false) {
            AcademicSession::where('id', '!=', $academic_session->id)->update(['is_current' => false]);
        }

        $academic_session->update($valid);

        return redirect()->route('sessions.index')->with('success', __('Session updated.'));
    }
}
