<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php
        $stats = $stats ?? [];
        $mode = $mode ?? (auth()->user()->isAdmin() ? 'admin' : 'telecaller');
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($mode === 'admin')
                {{-- Admin summary --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <a href="{{ route('students.index') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:border-indigo-300 hover:shadow-md transition">
                        <p class="text-sm font-medium text-slate-500">{{ __('Students') }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-800">{{ $stats['students'] ?? 0 }}</p>
                    </a>
                    <a href="{{ route('campaigns.index') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:border-indigo-300 hover:shadow-md transition">
                        <p class="text-sm font-medium text-slate-500">{{ __('Campaigns') }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-800">{{ $stats['campaigns'] ?? 0 }}</p>
                    </a>
                    <a href="{{ route('campaigns.index') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:border-emerald-300 hover:shadow-md transition">
                        <p class="text-sm font-medium text-slate-500">{{ __('Messages sent') }}</p>
                        <p class="mt-2 text-3xl font-bold text-emerald-700">{{ $stats['sent'] ?? 0 }}</p>
                    </a>
                    <a href="{{ route('campaigns.index') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:border-amber-300 hover:shadow-md transition">
                        <p class="text-sm font-medium text-slate-500">{{ __('Pending campaigns') }}</p>
                        <p class="mt-2 text-3xl font-bold text-amber-700">{{ $stats['pending'] ?? 0 }}</p>
                    </a>
                </div>

                @php
                    $leaderboard = $leaderboard ?? [];
                    $leaderboardFrom = $leaderboardFrom ?? null;
                    $leaderboardToEnd = $leaderboardToEnd ?? null;
                @endphp

                <div class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm">
                    <div class="px-6 pt-6 pb-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Telecaller leaderboard') }}</p>
                            @if ($leaderboardFrom && $leaderboardToEnd)
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ __('Last 7 days (same scoring as telecaller dashboard)') }}
                                </p>
                            @endif
                        </div>
                        <a href="{{ route('calls.report') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            {{ __('Open call report') }}
                        </a>
                    </div>
                    <div class="px-6 pb-6 overflow-x-auto">
                        @if (empty($leaderboard))
                            <p class="text-xs text-slate-500 py-3">
                                {{ __('No telecaller activity yet. Once staff start logging calls, their scores will appear here.') }}
                            </p>
                        @else
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Rank') }}</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Telecaller') }}</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Score') }}</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Calls') }}</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Connected %') }}</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Follow-up %') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($leaderboard as $idx => $row)
                                        @php
                                            $b = $row['breakdown'] ?? [];
                                        @endphp
                                        <tr class="{{ $idx === 0 ? 'bg-amber-50/60' : '' }}">
                                            <td class="px-3 py-2 text-slate-600">
                                                #{{ $idx + 1 }}
                                            </td>
                                            <td class="px-3 py-2">
                                                <a href="{{ route('admin.staff.show', $row['user']->id) }}"
                                                   class="text-slate-800 font-medium hover:text-indigo-600">
                                                    {{ $row['user']->name }}
                                                </a>
                                            </td>
                                            <td class="px-3 py-2 text-slate-800 font-semibold">
                                                {{ $row['score'] }}%
                                            </td>
                                            <td class="px-3 py-2 text-slate-600">
                                                {{ $b['total_calls'] ?? 0 }}
                                            </td>
                                            <td class="px-3 py-2 text-slate-600">
                                                {{ $b['connected_rate'] ?? 0 }}%
                                            </td>
                                            <td class="px-3 py-2 text-slate-600">
                                                {{ $b['followup_compliance'] ?? 0 }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            @else
                {{-- Telecaller personal dashboard --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <a href="{{ route('students.my-leads') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:border-indigo-300 hover:shadow-md transition">
                        <p class="text-sm font-medium text-slate-500">{{ __('My leads assigned') }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-800">{{ $stats['assigned_leads'] ?? 0 }}</p>
                    </a>
                    <a href="{{ route('students.followups') }}" class="bg-white rounded-2xl border border-emerald-200 shadow-sm p-6 hover:border-emerald-300 hover:shadow-md transition">
                        <p class="text-sm font-medium text-slate-500">{{ __('Open follow-ups (0–14 days)') }}</p>
                        <p class="mt-2 text-3xl font-bold text-emerald-700">{{ $stats['followups_window'] ?? 0 }}</p>
                    </a>
                    <a href="{{ route('students.followups') }}" class="bg-white rounded-2xl border border-amber-200 shadow-sm p-6 hover:border-amber-300 hover:shadow-md transition">
                        <p class="text-sm font-medium text-slate-500">{{ __('Overdue follow-ups') }}</p>
                        <p class="mt-2 text-3xl font-bold text-amber-700">{{ $stats['overdue_followups'] ?? 0 }}</p>
                    </a>
                    <a href="{{ route('calls.report') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:border-slate-300 hover:shadow-md transition">
                        <p class="text-sm font-medium text-slate-500">{{ __('Calls today') }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-800">{{ $stats['calls_today'] ?? 0 }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ __('Not connected today:') }}
                            <span class="font-semibold text-rose-600">{{ $stats['not_connected_today'] ?? 0 }}</span>
                            <span class="mx-1">·</span>
                            {{ __('Messages sent from my campaigns:') }}
                            <span class="font-semibold text-emerald-600">{{ $stats['messages_sent'] ?? 0 }}</span>
                        </p>
                    </a>
                </div>

                {{-- Telecaller rating --}}
                @php
                    $scoreToday   = $stats['score_today'] ?? null;
                    $scoreOverall = $stats['score_overall'] ?? null;
                    $labelFor = function ($pct) {
                        return match (true) {
                            $pct >= 85 => __('Top performer'),
                            $pct >= 70 => __('Strong'),
                            $pct >= 40 => __('Stable'),
                            default => __('Needs focus'),
                        };
                    };
                @endphp

                @if ($scoreToday)
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-8">
                        <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm p-6">
                            <p class="text-sm font-medium text-slate-500">{{ __('My score (today)') }}</p>
                            <div class="mt-2 flex items-baseline gap-2">
                                <p class="text-4xl font-extrabold text-indigo-700">{{ $scoreToday['score'] ?? 0 }}%</p>
                            </div>
                            <p class="mt-1 text-xs font-semibold text-slate-600">{{ $labelFor($scoreToday['score'] ?? 0) }}</p>
                            <p class="mt-2 text-xs text-slate-500">
                                {{ __('Calls:') }}
                                <span class="font-semibold text-slate-800">
                                    {{ $scoreToday['breakdown']['total_calls'] ?? 0 }}/{{ $scoreToday['breakdown']['daily_target'] ?? 25 }}
                                </span>
                                <span class="mx-1">·</span>
                                {{ __('Connected rate:') }}
                                <span class="font-semibold text-slate-800">{{ $scoreToday['breakdown']['connected_rate'] ?? 0 }}%</span>
                                <span class="mx-1">·</span>
                                {{ __('Follow-up compliance:') }}
                                <span class="font-semibold text-slate-800">{{ $scoreToday['breakdown']['followup_compliance'] ?? 0 }}%</span>
                            </p>
                        </div>

                        @if ($scoreOverall && ($scoreOverall['days'] ?? 0) > 0)
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                                <p class="text-sm font-medium text-slate-500">{{ __('Overall average (working days)') }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ __('Working days:') }} {{ $scoreOverall['days'] }}
                                </p>
                                <p class="mt-2 text-3xl font-extrabold text-slate-800">{{ $scoreOverall['score'] ?? 0 }}%</p>
                            </div>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>

