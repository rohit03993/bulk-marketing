<x-app-layout>
    @if (auth()->user()->isAdmin())
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>
    @endif

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
                {{-- Telecaller personal dashboard (app-style) --}}

                {{-- Hero banner --}}
                <div class="rounded-2xl bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 p-5 sm:p-6 text-white shadow-lg -mt-2">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-lg font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <div>
                            <h2 class="text-lg font-bold leading-tight">{{ __('My Dashboard') }}</h2>
                            <p class="text-sm text-indigo-100">{{ __('Track your leads and calls') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Lead status cards --}}
                @php
                    $cards = [
                        ['label' => __('Total Leads'), 'value' => $stats['assigned_leads'] ?? 0, 'href' => route('students.my-leads'), 'bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>'],
                        ['label' => __('New Leads'), 'value' => $stats['lead_new'] ?? 0, 'href' => route('students.my-leads', ['status' => 'lead', 'called' => '0']), 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>'],
                        ['label' => __('Follow-up'), 'value' => $stats['followups_window'] ?? 0, 'href' => route('students.followups'), 'bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                        ['label' => __('Converted'), 'value' => ($stats['lead_walkin_done'] ?? 0) + ($stats['lead_admission_done'] ?? 0), 'href' => route('students.my-leads', ['status' => 'admission_done']), 'bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                        ['label' => __('Exited'), 'value' => $stats['lead_not_interested'] ?? 0, 'href' => route('students.my-leads', ['status' => 'not_interested']), 'bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z"/>'],
                        ['label' => __('Calls Today'), 'value' => $stats['calls_today'] ?? 0, 'href' => route('calls.report'), 'bg' => 'bg-teal-100', 'text' => 'text-teal-700', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>'],
                        ['label' => __('Interested'), 'value' => $stats['lead_interested'] ?? 0, 'href' => route('students.my-leads', ['status' => 'interested']), 'bg' => 'bg-pink-100', 'text' => 'text-pink-700', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>'],
                        ['label' => __('Walk-in Done'), 'value' => $stats['lead_walkin_done'] ?? 0, 'href' => route('students.my-leads', ['status' => 'walkin_done']), 'bg' => 'bg-cyan-100', 'text' => 'text-cyan-700', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>'],
                        ['label' => __('Total Calls'), 'value' => $stats['total_calls_ever'] ?? 0, 'href' => route('calls.report'), 'bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>'],
                    ];
                @endphp

                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    @foreach ($cards as $card)
                        <a href="{{ $card['href'] }}" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex items-start justify-between hover:shadow-md hover:border-indigo-200 transition group">
                            <div>
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ $card['label'] }}</p>
                                <p class="mt-2 text-2xl sm:text-3xl font-extrabold {{ $card['text'] }}">{{ $card['value'] }}</p>
                            </div>
                            <span class="w-10 h-10 rounded-xl {{ $card['bg'] }} flex items-center justify-center shrink-0 group-hover:scale-110 transition">
                                <svg class="w-5 h-5 {{ $card['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $card['icon'] !!}</svg>
                            </span>
                        </a>
                    @endforeach
                </div>

                {{-- Quick actions --}}
                <div class="flex flex-wrap gap-3 mt-1">
                    <a href="{{ route('students.call-queue') }}" class="flex-1 min-w-[140px] flex items-center gap-3 bg-indigo-600 text-white rounded-2xl px-5 py-4 shadow-md hover:bg-indigo-700 transition">
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <div>
                            <p class="text-sm font-bold">{{ __('Start Calling') }}</p>
                            <p class="text-xs text-indigo-200">{{ __('Open call queue') }}</p>
                        </div>
                    </a>
                    <a href="{{ route('students.my-leads') }}" class="flex-1 min-w-[140px] flex items-center gap-3 bg-white text-slate-700 rounded-2xl px-5 py-4 shadow-sm border border-slate-200 hover:border-indigo-300 hover:shadow-md transition">
                        <svg class="w-6 h-6 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <div>
                            <p class="text-sm font-bold">{{ __('View All Leads') }}</p>
                            <p class="text-xs text-slate-400">{{ __('Browse assigned leads') }}</p>
                        </div>
                    </a>
                </div>

                {{-- Telecaller score --}}
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
                    $colorFor = function ($pct) {
                        return match (true) {
                            $pct >= 85 => 'text-emerald-600',
                            $pct >= 70 => 'text-blue-600',
                            $pct >= 40 => 'text-amber-600',
                            default => 'text-rose-600',
                        };
                    };
                @endphp

                @if ($scoreToday)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('My Score Today') }}</p>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ ($scoreToday['score'] ?? 0) >= 70 ? 'bg-emerald-100 text-emerald-700' : (($scoreToday['score'] ?? 0) >= 40 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                                    {{ $labelFor($scoreToday['score'] ?? 0) }}
                                </span>
                            </div>
                            <p class="mt-3 text-4xl font-extrabold {{ $colorFor($scoreToday['score'] ?? 0) }}">{{ $scoreToday['score'] ?? 0 }}%</p>
                            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                <span>{{ __('Calls') }}: <strong class="text-slate-700">{{ $scoreToday['breakdown']['total_calls'] ?? 0 }}/{{ $scoreToday['breakdown']['daily_target'] ?? 25 }}</strong></span>
                                <span>{{ __('Connected') }}: <strong class="text-slate-700">{{ $scoreToday['breakdown']['connected_rate'] ?? 0 }}%</strong></span>
                                <span>{{ __('Follow-up') }}: <strong class="text-slate-700">{{ $scoreToday['breakdown']['followup_compliance'] ?? 0 }}%</strong></span>
                            </div>
                        </div>
                        @if ($scoreOverall && ($scoreOverall['days'] ?? 0) > 0)
                            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('Overall Average') }}</p>
                                <p class="mt-3 text-4xl font-extrabold text-slate-800">{{ $scoreOverall['score'] ?? 0 }}%</p>
                                <p class="mt-3 text-xs text-slate-400">{{ __('Working days') }}: {{ $scoreOverall['days'] }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Activity summary --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">{{ __('Today\'s Activity') }}</p>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div>
                            <p class="text-2xl font-bold text-slate-800">{{ $stats['calls_today'] ?? 0 }}</p>
                            <p class="text-[11px] text-slate-500">{{ __('Calls Made') }}</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-rose-600">{{ $stats['not_connected_today'] ?? 0 }}</p>
                            <p class="text-[11px] text-slate-500">{{ __('Not Connected') }}</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-emerald-600">{{ $stats['messages_sent'] ?? 0 }}</p>
                            <p class="text-[11px] text-slate-500">{{ __('Messages Sent') }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

