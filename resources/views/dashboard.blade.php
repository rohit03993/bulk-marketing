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
                {{-- Admin reports (fixed session) --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ __('Admin Reports') }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ __('Academic session') }}: <span class="font-semibold text-slate-700">{{ $currentSessionName ?? '2025-26' }}</span></p>
                        </div>
                    </div>
                </div>

                {{-- KPI cards (reporting) --}}
                @php
                    $dashboardSessionId = (int) (
                        \App\Models\AcademicSession::where('name', $currentSessionName ?? '2025-26')->value('id')
                        ?? 0
                    );
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('Total students') }}</p>
                        <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $kpi['total_students'] ?? 0 }}</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-emerald-200 shadow-sm p-6">
                        <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wide">{{ __('Converted') }}</p>
                        <p class="mt-2 text-3xl font-extrabold text-emerald-700">
                            <a href="{{ route('students.index', ['lead_status' => 'converted', 'session_id' => $dashboardSessionId]) }}" class="hover:underline">
                                {{ $kpi['converted'] ?? 0 }}
                            </a>
                        </p>
                    </div>
                    <div class="bg-white rounded-2xl border border-amber-200 shadow-sm p-6">
                        <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide">{{ __('Follow-ups due') }}</p>
                        <p class="mt-2 text-3xl font-extrabold text-amber-700">{{ $kpi['followups_due'] ?? 0 }}</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-rose-200 shadow-sm p-6">
                        <p class="text-xs font-semibold text-rose-700 uppercase tracking-wide">{{ __('Blocked leads') }}</p>
                        <p class="mt-2 text-3xl font-extrabold text-rose-700">
                            <a href="{{ route('students.index', ['blocked' => 1, 'session_id' => $dashboardSessionId]) }}" class="hover:underline">
                                {{ $kpi['blocked'] ?? 0 }}
                            </a>
                        </p>
                    </div>
                </div>

                {{-- School -> class snapshot + telecaller due breakdown --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 pt-6 pb-3 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ __('School -> Class snapshot') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ __('Click a school to view class breakdown.') }}</p>
                            </div>
                        </div>
                        @php
                            $selectedSchoolId = (int) request('school_id', 0);
                            $perPage = (int) request('per_page', 10);
                            $perPage = $perPage > 0 ? $perPage : 10;
                        @endphp
                        <div class="px-6 pb-4">
                            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
                                <div class="flex-1">
                                    <label class="text-[11px] font-semibold text-slate-600">{{ __('Select school') }}</label>
                                    <select name="school_id" data-school-search="1"
                                            class="mt-1 w-full rounded-lg border-slate-200 bg-white text-sm px-3 py-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="0" {{ $selectedSchoolId === 0 ? 'selected' : '' }}>{{ __('All schools') }}</option>
                                        @foreach (($schoolOptions ?? collect()) as $opt)
                                            <option value="{{ $opt->school_id }}" {{ $selectedSchoolId === (int) $opt->school_id ? 'selected' : '' }}>
                                                {{ $opt->school_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block">{{ __('Per page') }}</label>
                                    <select name="per_page"
                                            class="mt-1 rounded-lg border-slate-200 bg-white text-sm px-3 py-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        @foreach ([5, 10, 20] as $opt)
                                            <option value="{{ $opt }}" {{ $perPage === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <input type="hidden" name="page" value="1" />
                                <button type="submit"
                                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition">
                                    {{ __('Apply') }}
                                </button>
                            </form>
                        </div>
                        <div class="px-6 pb-6 space-y-3">
                            @php $schoolBreakdown = $schoolBreakdown ?? collect(); @endphp
                            @php
                                $schoolBreakdownTotal = $schoolBreakdown->total ?? $schoolBreakdown->count();
                            @endphp
                            @if ((int) $schoolBreakdownTotal === 0)
                                <p class="text-xs text-slate-500 py-3">{{ __('No data for this session yet.') }}</p>
                            @else
                                @foreach ($schoolBreakdown as $school)
                                    <details class="group rounded-xl border border-slate-200 bg-white overflow-hidden">
                                        <summary class="cursor-pointer list-none px-4 py-3 flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-slate-900 truncate">{{ $school->school_name }}</p>
                                                <p class="text-xs text-slate-500 mt-0.5">
                                                    {{ __('Students') }}: <span class="font-semibold text-slate-700">{{ $school->total_students }}</span>
                                                    · {{ __('Classes') }}: <span class="font-semibold text-slate-700">{{ $school->class_sections_count }}</span>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800">
                                                    {{ __('Converted') }}: {{ $school->converted_count }}
                                                </span>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800">
                                                    {{ __('Due') }}: {{ $school->followups_due_count }}
                                                </span>
                                            </div>
                                        </summary>
                                        <div class="px-4 pb-4">
                                            @php $classes = $school->classes ?? collect(); @endphp
                                            @if ($classes->isEmpty())
                                                <p class="text-xs text-slate-500 py-2">{{ __('No class data.') }}</p>
                                            @else
                                                <div class="overflow-x-auto rounded-lg border border-slate-200">
                                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                                        <thead class="bg-slate-50">
                                                            <tr>
                                                                <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">{{ __('Class') }}</th>
                                                                <th class="px-3 py-2 text-right text-xs font-semibold text-slate-600">{{ __('Students') }}</th>
                                                                <th class="px-3 py-2 text-right text-xs font-semibold text-emerald-700">{{ __('Converted') }}</th>
                                                                <th class="px-3 py-2 text-right text-xs font-semibold text-amber-700">{{ __('Due') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-slate-100 bg-white">
                                                            @foreach ($classes as $cls)
                                                                @php $label = $cls->section_name ? ($cls->class_name . ' - ' . $cls->section_name) : $cls->class_name; @endphp
                                                                <tr class="hover:bg-slate-50/60">
                                                                    <td class="px-3 py-2 text-slate-800 font-medium">{{ $label }}</td>
                                                                    <td class="px-3 py-2 text-right text-slate-700">{{ $cls->total_students }}</td>
                                                                    <td class="px-3 py-2 text-right text-emerald-700 font-semibold">{{ $cls->converted_count }}</td>
                                                                    <td class="px-3 py-2 text-right text-amber-700 font-semibold">{{ $cls->followups_due_count }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                @endforeach
                                @if ($schoolBreakdown instanceof \Illuminate\Pagination\AbstractPaginator)
                                    @php
                                    $currentPage = $schoolBreakdown->currentPage();
                                    $lastPage = $schoolBreakdown->lastPage();
                                    $baseQuery = request()->except('page');
                                    $prevUrl = $currentPage > 1
                                        ? request()->url() . '?' . http_build_query(array_merge($baseQuery, ['page' => $currentPage - 1]))
                                        : null;
                                    $nextUrl = $currentPage < $lastPage
                                        ? request()->url() . '?' . http_build_query(array_merge($baseQuery, ['page' => $currentPage + 1]))
                                        : null;
                                    @endphp
                                    <div class="pt-3 flex items-center justify-between gap-3">
                                        <div>
                                            @if ($prevUrl)
                                                <a href="{{ $prevUrl }}"
                                                   class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition">
                                                    {{ __('Prev') }}
                                                </a>
                                            @else
                                                <span class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-semibold text-slate-400 bg-slate-50 border border-slate-200 transition opacity-50 cursor-not-allowed">
                                                    {{ __('Prev') }}
                                                </span>
                                            @endif
                                        </div>
                                        <div>
                                            @if ($nextUrl)
                                                <a href="{{ $nextUrl }}"
                                                   class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition">
                                                    {{ __('Next') }}
                                                </a>
                                            @else
                                                <span class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-semibold text-slate-400 bg-slate-50 border border-slate-200 transition opacity-50 cursor-not-allowed">
                                                    {{ __('Next') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 pt-6 pb-3">
                            <p class="text-sm font-semibold text-slate-900">{{ __('Follow-ups due by telecaller') }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ __('Overall (current session)') }}</p>
                        </div>
                        <div class="px-6 pb-6">
                            @php $telecallerAggs = $telecallerAggs ?? collect(); @endphp
                            @if ($telecallerAggs->isEmpty())
                                <p class="text-xs text-slate-500 py-3">{{ __('No telecaller data yet.') }}</p>
                            @else
                                <div class="overflow-x-auto rounded-lg border border-slate-200">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">{{ __('Telecaller') }}</th>
                                                <th class="px-3 py-2 text-right text-xs font-semibold text-amber-700">{{ __('Due') }}</th>
                                                <th class="px-3 py-2 text-right text-xs font-semibold text-emerald-700">{{ __('Converted') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @foreach ($telecallerAggs as $t)
                                                <tr class="hover:bg-slate-50/60">
                                                    <td class="px-3 py-2 text-slate-800 font-medium">{{ $t->telecaller_name }}</td>
                                                    <td class="px-3 py-2 text-right text-amber-700 font-semibold">{{ $t->followups_due_count }}</td>
                                                    <td class="px-3 py-2 text-right text-emerald-700 font-semibold">{{ $t->converted_count }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    @php
                        $assignFromValue = isset($assignFromAt) && $assignFromAt ? $assignFromAt->toDateString() : '';
                        $assignToValue = isset($assignToAt) && $assignToAt ? $assignToAt->toDateString() : '';
                    @endphp
                    <div class="px-6 pt-6 pb-3 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ __('Lead assignment activity') }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ __('Shows who assigned/re-distributed and for which school.') }}</p>
                        </div>
                        <form method="GET" action="{{ route('dashboard') }}" class="flex items-end gap-2">
                            <input type="date" name="assign_from" value="{{ $assignFromValue }}"
                                   class="rounded-lg border-slate-200 text-xs px-2 py-1.5">
                            <input type="date" name="assign_to" value="{{ $assignToValue }}"
                                   class="rounded-lg border-slate-200 text-xs px-2 py-1.5">
                            <button type="submit" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200">
                                {{ __('Apply') }}
                            </button>
                        </form>
                    </div>
                    <div class="px-6 pb-6">
                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">{{ __('Date') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">{{ __('Student') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">{{ __('School') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">{{ __('From -> To') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">{{ __('By') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse (($assignmentActivity ?? collect()) as $a)
                                        <tr>
                                            <td class="px-3 py-2 text-xs text-slate-600">{{ $a->transferred_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                            <td class="px-3 py-2 text-slate-700">{{ $a->student?->name ?? '—' }}</td>
                                            <td class="px-3 py-2 text-slate-600">{{ $a->student?->classSection?->school?->name ?? '—' }}</td>
                                            <td class="px-3 py-2 text-slate-700">{{ $a->fromUser?->name ?? __('Unassigned') }} → {{ $a->toUser?->name ?? '—' }}</td>
                                            <td class="px-3 py-2 text-slate-600">{{ $a->transferredByUser?->name ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-3 py-6 text-xs text-slate-500 text-center">{{ __('No assignment activity in selected date range.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if (($assignmentActivity ?? null) && $assignmentActivity instanceof \Illuminate\Pagination\AbstractPaginator && $assignmentActivity->hasPages())
                            <div class="mt-3">{{ $assignmentActivity->links() }}</div>
                        @endif
                    </div>
                </div>

                @php
                    $leaderboard = $leaderboard ?? [];
                    $leaderboardFrom = $leaderboardFrom ?? null;
                    $leaderboardToEnd = $leaderboardToEnd ?? null;
                @endphp

                <div class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm">
                    <div class="px-6 pt-6 pb-3 flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Telecaller leaderboard') }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ __('Full history (since first call) — score based on all calls)') }}
                            </p>
                            <p class="mt-1 text-[11px] text-slate-400">
                                {{ __('Last updated') }}: <span id="leaderboardLastUpdated">{{ now()->format('d M, h:i:s A') }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="refreshLeaderboardBtn"
                                    class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition">
                                {{ __('Refresh') }}
                            </button>
                            <a href="{{ route('calls.report') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                {{ __('Open call report') }}
                            </a>
                        </div>
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
                                                <div class="flex flex-col">
                                                    <span title="{{ __('Score = (Outcome*80) + (Notes*8) + (Engagement*5) + (Follow-up*5) + (VolumeRatio*2). Outcome/Notes/Engagement are derived from connected calls.') }}">
                                                        {{ $row['score'] }}%
                                                    </span>
                                                    <span class="text-[11px] text-slate-500 font-normal mt-1 leading-snug">
                                                        {{ __('Outcome') }}: {{ $b['outcome_score_percent'] ?? 0 }}% ·
                                                        {{ __('Notes') }}: {{ $b['notes_score_percent'] ?? 0 }}% ·
                                                        {{ __('Eng.') }}: {{ $b['engagement_score_percent'] ?? 0 }}% ·
                                                        {{ __('Follow-up') }}: {{ $b['followup_compliance'] ?? 0 }}%<br/>
                                                        {{ __('Adm') }}: {{ $b['lead_admission'] ?? 0 }} ·
                                                        {{ __('Walk-in') }}: {{ $b['lead_walkin'] ?? 0 }} ·
                                                        {{ __('Volume') }}: {{ $b['total_calls'] ?? 0 }}/{{ $b['daily_target'] ?? 25 }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-slate-600">
                                                {{ $b['total_calls'] ?? 0 }}
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

                <script>
                    (function () {
                        const btn = document.getElementById('refreshLeaderboardBtn');
                        if (btn) {
                            btn.addEventListener('click', function () {
                                window.location.reload();
                            });
                        }

                        // Auto-refresh (admin only) so scores stay current without manual refresh.
                        // Keeps it lightweight by reloading the page at a safe interval.
                        const AUTO_REFRESH_MS = 60 * 1000; // 1 minute
                        window.setInterval(function () {
                            // If user is interacting (modal/open) we still reload; keeping simple as requested.
                            window.location.reload();
                        }, AUTO_REFRESH_MS);
                    })();
                </script>
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
                        ['label' => __('Converted'), 'value' => ($stats['lead_walkin_done'] ?? 0) + ($stats['lead_admission_done'] ?? 0), 'href' => route('students.my-leads', ['status' => 'converted']), 'bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
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
                    <a href="{{ route('students.my-leads') }}?added_by_me=1" class="flex-1 min-w-[140px] flex items-center gap-3 bg-white text-slate-700 rounded-2xl px-5 py-4 shadow-sm border border-slate-200 hover:border-indigo-300 hover:shadow-md transition">
                        <svg class="w-6 h-6 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <div>
                            <p class="text-sm font-bold">{{ __('My Added Leads') }}</p>
                            <p class="text-xs text-slate-400">{{ __('Leads you personally added') }}</p>
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

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">{{ __('Recent Lead Assignments To Me') }}</p>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">{{ __('Date') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">{{ __('Student') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">{{ __('School') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">{{ __('From') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse (($assignmentActivity ?? collect()) as $a)
                                    <tr>
                                        <td class="px-3 py-2 text-xs text-slate-600">{{ $a->transferred_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ $a->student?->name ?? '—' }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $a->student?->classSection?->school?->name ?? '—' }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $a->fromUser?->name ?? __('Unassigned') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-6 text-xs text-slate-500 text-center">{{ __('No recent assignments.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Call reporting (New vs Follow-up) --}}
                <div class="bg-indigo-50 rounded-2xl shadow-lg shadow-indigo-100/60 border border-indigo-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-1 rounded-full bg-indigo-600"></span>
                            <p class="text-sm font-semibold text-indigo-900">{{ __('Call reporting (New vs Follow-up)') }}</p>
                        </div>
                        <a href="{{ route('calls.report') }}"
                           class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">
                            {{ __('Open call report') }} →
                        </a>
                    </div>

                    <div class="mb-4 rounded-xl bg-indigo-100/60 border border-indigo-200 p-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold text-indigo-900">{{ __('Date range (New vs Follow-up)') }}</p>
                            <p class="mt-1 text-[11px] text-indigo-900/70">
                                {{ __('Using range:') }} <span class="font-semibold">{{ $rangeLabel ?? '' }}</span>
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-white text-indigo-700 border border-indigo-200">
                                {{ __('Pending uses today queue') }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-2 mb-3 rounded-xl border border-indigo-200 bg-indigo-100/50 p-3 flex items-start gap-2">
                        <span class="flex h-8 w-2 rounded-lg bg-indigo-600"></span>
                        <div>
                            <p class="text-sm font-semibold text-indigo-900">{{ __('Pending calls (today queue)') }}</p>
                            <p class="text-xs text-indigo-900/60">{{ __('Queue + due/overdue follow-ups for today.') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm mt-4 mb-4">
                        <div class="bg-white rounded-xl p-3 border border-indigo-100">
                            <p class="text-xs text-slate-500">{{ __('Pending calls (queue)') }}</p>
                            <p class="mt-1 text-xl font-bold text-slate-800">{{ (int) ($pendingCalls['pending_total'] ?? 0) }}</p>
                        </div>
                        <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-100">
                            <p class="text-xs text-emerald-700">{{ __('Pending: New (uncalled)') }}</p>
                            <p class="mt-1 text-xl font-bold text-emerald-700">{{ (int) ($pendingCalls['pending_new'] ?? 0) }}</p>
                        </div>
                        <div class="bg-amber-50 rounded-xl p-3 border border-amber-100">
                            <p class="text-xs text-amber-700">{{ __('Pending: Follow-up') }}</p>
                            <p class="mt-1 text-xl font-bold text-amber-700">{{ (int) ($pendingCalls['pending_followup'] ?? 0) }}</p>
                        </div>
                    </div>

                    <div class="mt-2 mb-3 rounded-xl border border-amber-200 bg-amber-100/50 p-3 flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-amber-900">
                            {{ __('Calls made (range):') }} <span class="font-normal">{{ $rangeLabel ?? '' }}</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm mb-4">
                        <div class="bg-indigo-50 rounded-xl p-3 border border-indigo-100">
                            <p class="text-xs text-indigo-700">{{ __('New calls in this range') }}</p>
                            <p class="mt-1 text-xl font-bold text-indigo-700">{{ (int) ($callsRangeTotals['new_calls'] ?? 0) }}</p>
                        </div>
                        <div class="bg-amber-50 rounded-xl p-3 border border-amber-100">
                            <p class="text-xs text-amber-700">{{ __('Follow-up calls in this range') }}</p>
                            <p class="mt-1 text-xl font-bold text-amber-700">{{ (int) ($callsRangeTotals['followup_calls'] ?? 0) }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                            <p class="text-xs text-slate-500">{{ __('Total calls in this range') }}</p>
                            <p class="mt-1 text-xl font-bold text-slate-800">{{ (int) ($callsRangeTotals['total_calls'] ?? 0) }}</p>
                        </div>
                    </div>

                    <div class="mt-2 mb-3 rounded-xl border border-emerald-200 bg-emerald-100/50 p-3 flex items-center gap-2">
                        <span class="flex h-8 w-2 rounded-lg bg-emerald-600"></span>
                        <p class="text-sm font-semibold text-emerald-900">
                            {{ __('Daily new vs follow-up table') }}
                            <span class="font-normal">{{ $dailyCapNote ?? '' }}</span>
                        </p>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-indigo-100 overflow-hidden">
                        <table class="min-w-full divide-y divide-blue-100 text-sm">
                            <thead class="bg-gradient-to-r from-blue-50 to-sky-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-blue-800">{{ __('Date') }}</th>
                                    <th class="px-4 py-3 text-right font-semibold text-emerald-700">{{ __('New calls') }}</th>
                                    <th class="px-4 py-3 text-right font-semibold text-amber-700">{{ __('Follow-up calls') }}</th>
                                    <th class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-50/80 bg-white">
                                @forelse ($dailyNewFollowup ?? [] as $row)
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <td class="px-4 py-3 text-slate-700 font-medium">{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-emerald-700">{{ (int) ($row['new_calls'] ?? 0) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-amber-700">{{ (int) ($row['followup_calls'] ?? 0) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-700">{{ (int) ($row['total_calls'] ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-sm text-slate-500 text-center">{{ __('No calls found in this date range.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

