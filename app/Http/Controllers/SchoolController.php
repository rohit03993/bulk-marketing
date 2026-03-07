<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index()
    {
        $schools = School::orderBy('name')->paginate(15);

        return view('crm.schools.index', compact('schools'));
    }

    public function create()
    {
        return view('crm.schools.create');
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'contact_email' => 'nullable|email',
        ]);
        $valid['contact_person'] = null;
        $valid['contact_phone'] = null;
        School::create($valid);

        return redirect()->route('schools.index')->with('success', __('School created.'));
    }

    public function edit(School $school)
    {
        return view('crm.schools.edit', compact('school'));
    }

    public function update(Request $request, School $school)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'contact_email' => 'nullable|email',
        ]);
        $valid['contact_person'] = null;
        $valid['contact_phone'] = null;
        $school->update($valid);

        return redirect()->route('schools.index')->with('success', __('School updated.'));
    }
}
