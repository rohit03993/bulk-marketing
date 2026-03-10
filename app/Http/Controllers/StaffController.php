<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\StudentCall;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Services\TelecallerScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    public function show(User $staff)
    {
        abort_if($staff->isAdmin(), 404);

        $now = now();
        $today = $now->copy();

        $scoreService = new TelecallerScoreService();
        $dailyTarget = 25;
        $scoreToday = $scoreService->compute($staff->id, $today, $today, $dailyTarget);
        $scoreOverall = $scoreService->computeOverallAverage($staff->id, $dailyTarget);

        $callsQuery = StudentCall::with(['student.classSection.school'])
            ->where('user_id', $staff->id)
            ->orderByDesc('called_at');
        $recentCalls = (clone $callsQuery)->limit(20)->get();

        $callsTotal = (clone $callsQuery)->count();
        $callsConnected = (clone $callsQuery)->where('call_status', StudentCall::STATUS_CONNECTED)->count();

        $notConnectedStatuses = [
            StudentCall::STATUS_NO_ANSWER,
            StudentCall::STATUS_BUSY,
            StudentCall::STATUS_SWITCHED_OFF,
            StudentCall::STATUS_NOT_REACHABLE,
            StudentCall::STATUS_WRONG_NUMBER,
            StudentCall::STATUS_CALLBACK,
        ];
        $callsNotConnected = (clone $callsQuery)->whereIn('call_status', $notConnectedStatuses)->count();

        $students = Student::with(['classSection.school'])
            ->where('assigned_to', $staff->id)
            ->orderBy('name')
            ->limit(50)
            ->get();

        $campaigns = Campaign::with(['school', 'template'])
            ->where('shot_by', $staff->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $messagesSent = CampaignRecipient::where('status', 'sent')
            ->whereHas('campaign', fn ($q) => $q->where('shot_by', $staff->id))
            ->count();

        return view('admin.staff.show', [
            'staff' => $staff,
            'scoreToday' => $scoreToday,
            'scoreOverall' => $scoreOverall,
            'callsSummary' => [
                'total' => $callsTotal,
                'connected' => $callsConnected,
                'not_connected' => $callsNotConnected,
            ],
            'recentCalls' => $recentCalls,
            'students' => $students,
            'campaigns' => $campaigns,
            'messagesSent' => $messagesSent,
        ]);
    }

    public function index()
    {
        $staff = User::where('is_admin', false)
            ->with('createdBy')
            ->orderBy('name')
            ->get();

        return view('admin.staff.index', compact('staff'));
    }

    public function create()
    {
        return view('admin.staff.create');
    }

    public function edit(User $staff)
    {
        abort_if($staff->isAdmin(), 404);

        return view('admin.staff.edit', compact('staff'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'can_access_schools' => ['sometimes', 'boolean'],
            'can_access_students' => ['sometimes', 'boolean'],
            'can_access_campaigns' => ['sometimes', 'boolean'],
            'can_access_templates' => ['sometimes', 'boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => false,
            'created_by' => auth()->id(),
            'can_access_schools' => (bool) ($validated['can_access_schools'] ?? false),
            'can_access_students' => (bool) ($validated['can_access_students'] ?? false),
            'can_access_campaigns' => (bool) ($validated['can_access_campaigns'] ?? false),
            'can_access_templates' => (bool) ($validated['can_access_templates'] ?? false),
        ]);

        return redirect()->route('admin.staff.index')
            ->with('success', __('Staff :name has been added.', ['name' => $validated['name']]));
    }

    public function update(Request $request, User $staff)
    {
        abort_if($staff->isAdmin(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $staff->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'can_access_schools' => ['sometimes', 'boolean'],
            'can_access_students' => ['sometimes', 'boolean'],
            'can_access_campaigns' => ['sometimes', 'boolean'],
            'can_access_templates' => ['sometimes', 'boolean'],
        ]);

        $update = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'can_access_schools' => (bool) ($validated['can_access_schools'] ?? false),
            'can_access_students' => (bool) ($validated['can_access_students'] ?? false),
            'can_access_campaigns' => (bool) ($validated['can_access_campaigns'] ?? false),
            'can_access_templates' => (bool) ($validated['can_access_templates'] ?? false),
        ];

        if (! empty($validated['password'] ?? null)) {
            $update['password'] = Hash::make($validated['password']);
        }

        $staff->update($update);

        return redirect()->route('admin.staff.index')
            ->with('success', __('Staff :name has been updated.', ['name' => $validated['name']]));
    }
}
