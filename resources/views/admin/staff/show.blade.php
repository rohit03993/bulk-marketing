<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-1 rounded-full bg-blue-500"></span>
                <div>
                    <h2 class="font-bold text-xl text-slate-800 leading-tight">
                        {{ $staff->name }}
                    </h2>
                    <p class="mt-0.5 text-xs text-blue-600">
                        {{ __('Telecaller performance overview') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.staff.index') }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200">
                    ← {{ __('Back to Staff') }}
                </a>
                <a href="{{ route('admin.staff.edit', $staff) }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-sm">
                    {{ __('Edit staff') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-sky-50 to-white min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Telecaller quick switch (admin) --}}
            @if (isset($telecallerOptions) && $telecallerOptions->isNotEmpty())
                <div class="bg-blue-50 rounded-2xl shadow-lg shadow-blue-100/50 border border-blue-200 p-5">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-blue-900">{{ __('Telecaller selection') }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('Jump to another staff performance page') }}</p>
                        </div>
                        <div class="min-w-[260px]">
                            <label class="block text-xs font-medium text-blue-700">{{ __('Telecaller') }}</label>
                            <select id="adminStaffSelect"
                                    class="mt-1 block w-full rounded-lg border-blue-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 text-slate-700">
                                @foreach ($telecallerOptions as $u)
                                    <option value="{{ $u->id }}" {{ (int) $u->id === (int) $staff->id ? 'selected' : '' }}>
                                        {{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <script>
                    (function () {
                        const sel = document.getElementById('adminStaffSelect');
                        if (!sel) return;
                        sel.addEventListener('change', function () {
                            window.location.href = '{{ url('admin/staff') }}/' + this.value;
                        });
                    })();
                </script>
            @endif

            {{-- Filters --}}
            <div class="bg-blue-50 rounded-2xl shadow-lg shadow-blue-100/50 border border-blue-200 p-5">
                <div class="flex items-center gap-2 mb-5 rounded-xl bg-blue-100/70 border border-blue-200/70 px-4 py-3">
                    <span class="flex h-8 w-1 rounded-full bg-blue-600"></span>
                    <p class="text-sm font-semibold text-blue-900">{{ __('Filter data') }}</p>
                </div>
                @php
                    $selectedSchoolId = (int) request()->input('school_id', 0);
                    $selectedClassSectionId = (int) request()->input('class_section_id', 0);
                    $addedByMeChecked = (bool) request()->boolean('added_by_me');
                    $schools = $schools ?? collect();
                    $classSections = $classSections ?? collect();
                    $leadStatusOptions = $leadStatusOptions ?? [];
                @endphp
                <form id="staffFilterForm" method="get" action="{{ route('admin.staff.show', $staff) }}" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label for="from_date" class="block text-xs font-medium text-blue-700">{{ __('From date') }}</label>
                        <input type="date" id="from_date" name="from_date" value="{{ $filterFrom }}"
                               class="mt-1 block w-full rounded-lg border-blue-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 text-slate-700">
                    </div>
                    <div>
                        <label for="to_date" class="block text-xs font-medium text-blue-700">{{ __('To date') }}</label>
                        <input type="date" id="to_date" name="to_date" value="{{ $filterTo }}"
                               class="mt-1 block w-full rounded-lg border-blue-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 text-slate-700">
                    </div>
                    <div class="min-w-[230px]">
                        <label class="block text-[11px] font-semibold text-blue-700">{{ __('Quick range') }}</label>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <button type="button"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-white hover:bg-blue-50 text-blue-800 border border-blue-200 transition"
                                    onclick="setStaffFilterRange('today')">
                                {{ __('Today') }}
                            </button>
                            <button type="button"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-white hover:bg-blue-50 text-blue-800 border border-blue-200 transition"
                                    onclick="setStaffFilterRange('7')">
                                {{ __('Last 7 days') }}
                            </button>
                            <button type="button"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-white hover:bg-blue-50 text-blue-800 border border-blue-200 transition"
                                    onclick="setStaffFilterRange('30')">
                                {{ __('Last 30 days') }}
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="lead_status" class="block text-xs font-medium text-blue-700">{{ __('Lead status') }}</label>
                        <select id="lead_status" name="lead_status" class="mt-1 block w-full rounded-lg border-blue-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 text-slate-700">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($leadStatusOptions as $value => $label)
                                <option value="{{ $value }}" {{ ($filterLeadStatus ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="school_id" class="block text-xs font-medium text-blue-700">{{ __('School') }}</label>
                        <select id="school_id" name="school_id" data-school-search="1" class="mt-1 block w-full rounded-lg border-blue-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 text-slate-700">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" {{ $selectedSchoolId === (int) $school->id ? 'selected' : '' }}>
                                    {{ $school->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="class_section_id" class="block text-xs font-medium text-blue-700">{{ __('Class / Section') }}</label>
                        <select id="class_section_id" name="class_section_id" class="mt-1 block w-full rounded-lg border-blue-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 text-slate-700">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($classSections as $cs)
                                <option value="{{ $cs->id }}" {{ $selectedClassSectionId === (int) $cs->id ? 'selected' : '' }}>
                                    {{ $cs->class_name }}{{ $cs->section_name ? ' - ' . $cs->section_name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[180px]">
                        <label class="flex items-center gap-2 text-xs font-medium text-blue-700">
                            <input
                                type="checkbox"
                                name="added_by_me"
                                value="1"
                                {{ $addedByMeChecked ? 'checked' : '' }}
                                class="rounded border-blue-200 text-blue-600 focus:ring-blue-500"
                            />
                            {{ __('Added by this telecaller') }}
                        </label>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-200/50 transition">
                        {{ __('Apply') }}
                    </button>
                    <a href="{{ route('admin.staff.show', $staff) }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition">
                        {{ __('Clear') }}
                    </a>
                </form>
                <script>
                    function setStaffFilterRange(mode) {
                        const fromEl = document.getElementById('from_date');
                        const toEl = document.getElementById('to_date');
                        const form = document.getElementById('staffFilterForm');
                        if (!fromEl || !toEl || !form) return;

                        const today = new Date();
                        const toISO = (d) => {
                            const yyyy = d.getFullYear();
                            const mm = String(d.getMonth() + 1).padStart(2, '0');
                            const dd = String(d.getDate()).padStart(2, '0');
                            return yyyy + '-' + mm + '-' + dd;
                        };

                        if (mode === 'today') {
                            fromEl.value = toISO(today);
                            toEl.value = toISO(today);
                        } else {
                            const days = parseInt(mode, 10) || 7;
                            const from = new Date(today);
                            from.setDate(today.getDate() - (days - 1));
                            fromEl.value = toISO(from);
                            toEl.value = toISO(today);
                        }
                        form.submit();
                    }
                </script>
            </div>

            @php
                $exitedFilterUrl = route('admin.staff.show', array_merge(
                    ['staff' => $staff->id],
                    request()->except('lead_status', 'students_page'),
                    ['lead_status' => 'not_interested']
                ));
                $convertedFilterUrl = route('admin.staff.show', array_merge(
                    ['staff' => $staff->id],
                    request()->except('lead_status', 'students_page'),
                    ['lead_status' => 'converted']
                ));
            @endphp

            {{-- Top summary cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-2xl shadow-lg shadow-blue-100/40 border border-blue-100 p-5 overflow-hidden relative">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-blue-400/20 to-transparent rounded-bl-full"></div>
                    <p class="text-xs font-medium text-blue-600">{{ __('My score (today)') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-blue-600">
                        {{ $scoreToday['score'] ?? 0 }}%
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ __('Calls:') }}
                        <span class="font-semibold text-slate-700">
                            {{ $scoreToday['breakdown']['total_calls'] ?? 0 }}/{{ $scoreToday['breakdown']['daily_target'] ?? 25 }}
                        </span>
                    </p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg shadow-sky-100/40 border border-sky-100 p-5 overflow-hidden relative">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-sky-400/20 to-transparent rounded-bl-full"></div>
                    <p class="text-xs font-medium text-sky-600">{{ __('Overall average (working days)') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-sky-700">
                        {{ $scoreOverall['score'] ?? 0 }}%
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ __('Working days:') }} {{ $scoreOverall['days'] ?? 0 }}
                    </p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg shadow-blue-100/40 border border-blue-100 p-5 overflow-hidden relative">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-blue-500/15 to-transparent rounded-bl-full"></div>
                    <p class="text-xs font-medium text-blue-600">
                        {{ $addedByMeChecked ? __('Leads added by telecaller') : __('Students assigned') }}
                    </p>
                    <p class="mt-2 text-3xl font-extrabold text-blue-700">
                        {{ $assignedTotal ?? $students->total() }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ __('Converted:') }}
                        <a href="{{ $convertedFilterUrl }}" class="font-semibold text-emerald-700 hover:text-emerald-800 hover:underline">
                            {{ ($convertedWalkin + $convertedAdmission) }}
                        </a>
                        · {{ __('Exited:') }}
                        <a href="{{ $exitedFilterUrl }}" class="font-semibold text-rose-700 hover:text-rose-800 hover:underline">
                            {{ $exitedNotInterested }}
                        </a>
                    </p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg shadow-indigo-100/40 border border-indigo-100 p-5 overflow-hidden relative">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-indigo-400/20 to-transparent rounded-bl-full"></div>
                    <p class="text-xs font-medium text-indigo-600">{{ __('Converted') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-indigo-700">
                        <a href="{{ $convertedFilterUrl }}" class="hover:underline">
                            {{ ($convertedWalkin + $convertedAdmission) }}
                        </a>
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ __('Walk-ins:') }} <span class="font-semibold text-slate-700">{{ $convertedWalkin }}</span>
                        · {{ __('Admissions:') }} <span class="font-semibold text-slate-700">{{ $convertedAdmission }}</span>
                    </p>
                </div>
            </div>

            {{-- Lead assignment activity --}}
            <div class="bg-violet-50 rounded-2xl shadow-lg shadow-violet-100/60 border border-violet-200 p-5">
                <div class="flex items-center gap-2 mb-4 rounded-xl bg-violet-100/70 border border-violet-200/70 px-4 py-3">
                    <span class="flex h-8 w-1 rounded-full bg-violet-600"></span>
                    <p class="text-sm font-semibold text-violet-900">{{ __('Lead assignment history') }}</p>
                </div>
                <p class="text-xs text-slate-500 mb-3">{{ __('Shows assignments/re-distributions for this telecaller with school and date.') }}</p>
                <div class="overflow-x-auto rounded-xl border border-violet-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-violet-100 text-sm">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-violet-800">{{ __('Date') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-violet-800">{{ __('Student') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-violet-800">{{ __('School') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-violet-800">{{ __('From -> To') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-violet-800">{{ __('By') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-violet-50/80 bg-white">
                            @forelse (($assignmentActivity ?? collect()) as $a)
                                <tr>
                                    <td class="px-4 py-3 text-xs text-slate-600">{{ $a->transferred_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $a->student?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $a->student?->classSection?->school?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $a->fromUser?->name ?? __('Unassigned') }} → {{ $a->toUser?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $a->transferredByUser?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-sm text-slate-500 text-center">{{ __('No assignment activity in selected range.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if (($assignmentActivity ?? null) && $assignmentActivity->hasPages())
                    <div class="mt-3">{{ $assignmentActivity->links() }}</div>
                @endif
            </div>

            {{-- Calls summary (all time + optional range) --}}
            <div class="bg-sky-50 rounded-2xl shadow-lg shadow-sky-100/60 border border-sky-200 p-5">
                <div class="flex items-center justify-between mb-5 rounded-xl bg-sky-100/70 border border-sky-200/70 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-1 rounded-full bg-sky-600"></span>
                        <p class="text-sm font-semibold text-sky-900">{{ __('Call activity summary') }}</p>
                    </div>
                    <a href="{{ route('calls.report', ['staff_id' => $staff->id]) }}"
                       class="text-xs font-semibold text-sky-700 hover:text-sky-900">
                        {{ __('Open full call report') }} →
                    </a>
                </div>
                <p class="text-xs font-medium text-sky-800 mb-3">{{ __('Total calls till now (all time)') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                        <p class="text-xs text-slate-500">{{ __('Total calls') }}</p>
                        <p class="mt-1 text-xl font-bold text-blue-800">
                            {{ $callsSummary['total'] ?? 0 }}
                        </p>
                    </div>
                    <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-100">
                        <p class="text-xs text-emerald-700">{{ __('Connected') }}</p>
                        <p class="mt-1 text-xl font-bold text-emerald-700">
                            {{ $callsSummary['connected'] ?? 0 }}
                        </p>
                    </div>
                    <div class="bg-rose-50 rounded-xl p-3 border border-rose-100">
                        <p class="text-xs text-rose-700">{{ __('Not connected') }}</p>
                        <p class="mt-1 text-xl font-bold text-rose-600">
                            {{ $callsSummary['not_connected'] ?? 0 }}
                        </p>
                    </div>
                </div>
                @if (isset($callsSummaryFiltered))
                    <p class="text-xs font-medium text-sky-800 mt-4 mb-2">{{ __('In selected date range') }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div class="bg-blue-50/50 rounded-xl p-3 border border-blue-100">
                            <p class="text-xs text-slate-500">{{ __('Total calls') }}</p>
                            <p class="mt-1 text-lg font-semibold text-blue-800">{{ $callsSummaryFiltered['total'] ?? 0 }}</p>
                        </div>
                        <div class="bg-emerald-50/50 rounded-xl p-3 border border-emerald-100">
                            <p class="text-xs text-slate-500">{{ __('Connected') }}</p>
                            <p class="mt-1 text-lg font-semibold text-emerald-700">{{ $callsSummaryFiltered['connected'] ?? 0 }}</p>
                        </div>
                        <div class="bg-rose-50/50 rounded-xl p-3 border border-rose-100">
                            <p class="text-xs text-slate-500">{{ __('Not connected') }}</p>
                            <p class="mt-1 text-lg font-semibold text-rose-600">{{ $callsSummaryFiltered['not_connected'] ?? 0 }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Pending + New vs Follow-up reporting (telecaller-first) --}}
            <div class="bg-indigo-50 rounded-2xl shadow-lg shadow-indigo-100/60 border border-indigo-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-1 rounded-full bg-indigo-600"></span>
                        <p class="text-sm font-semibold text-indigo-900">{{ __('Call reporting (New vs Follow-up)') }}</p>
                    </div>
                    <a href="{{ route('calls.report', ['staff_id' => $staff->id]) }}"
                       class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">
                        {{ __('Open call report') }} →
                    </a>
                </div>

                @php
                    $rangeLabel = ($filterFrom ?? '') && ($filterTo ?? '')
                        ? ($filterFrom . ' to ' . $filterTo)
                        : __('Last 30 days');
                    $dailyCapNote = __('(daily table shows up to 14 days for speed)');
                @endphp

                <div class="mb-4 rounded-xl bg-indigo-100/60 border border-indigo-200 p-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold text-indigo-900">{{ __('Date range (New vs Follow-up)') }}</p>
                        <p class="mt-1 text-[11px] text-indigo-900/70">
                            {{ __('Using range:') }} <span class="font-semibold">{{ $rangeLabel }}</span>
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
                        <p class="text-xs text-indigo-900/60">{{ __('Queue + due/overdue follow-ups (not historical range).') }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm mb-4">
                    <div class="bg-white rounded-xl p-3 border border-indigo-100">
                        <p class="text-xs text-slate-500">{{ __('Pending calls (today queue + due/overdue)') }}</p>
                        <p class="mt-1 text-xl font-bold text-slate-800">{{ (int) ($pendingCalls['pending_total'] ?? 0) }}</p>
                    </div>
                    <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-100">
                        <p class="text-xs text-emerald-700">{{ __('Pending: New (never called)') }}</p>
                        <p class="mt-1 text-xl font-bold text-emerald-700">{{ (int) ($pendingCalls['pending_new'] ?? 0) }}</p>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3 border border-amber-100">
                        <p class="text-xs text-amber-700">{{ __('Pending: Follow-up') }}</p>
                        <p class="mt-1 text-xl font-bold text-amber-700">{{ (int) ($pendingCalls['pending_followup'] ?? 0) }}</p>
                    </div>
                </div>

                <div class="mt-2 mb-3 rounded-xl border border-amber-200 bg-amber-100/50 p-3 flex items-center gap-2">
                    <span class="flex h-8 w-2 rounded-lg bg-amber-500"></span>
                    <p class="text-sm font-semibold text-amber-900">
                        {{ __('Calls made (range):') }} <span class="font-normal">{{ $rangeLabel }}</span>
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
                        <span class="font-normal">{{ $rangeLabel }}</span>
                        <span class="font-normal">{{ $dailyCapNote }}</span>
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

            {{-- Daily breakdown --}}
            <div class="bg-emerald-50 rounded-2xl shadow-lg shadow-emerald-100/60 border border-emerald-200 p-5">
                <div class="flex items-center gap-2 mb-4 rounded-xl bg-emerald-100/70 border border-emerald-200/70 px-4 py-3">
                    <span class="flex h-8 w-1 rounded-full bg-emerald-600"></span>
                    <p class="text-sm font-semibold text-emerald-900">{{ __('Daily breakdown') }}</p>
                </div>
                <p class="text-xs text-slate-500 mb-4">
                    @if (isset($filterFrom) && isset($filterTo))
                        {{ __('Calls per day in selected range.') }}
                    @else
                        {{ __('Last 30 days (or set From/To dates to filter).') }}
                    @endif
                </p>
                <div class="overflow-x-auto rounded-xl border border-blue-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-blue-100 text-sm">
                        <thead class="bg-gradient-to-r from-blue-50 to-sky-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-blue-800">{{ __('Date') }}</th>
                                <th class="px-4 py-3 text-right font-semibold text-blue-800">{{ __('Total calls') }}</th>
                                <th class="px-4 py-3 text-right font-semibold text-blue-800">{{ __('Connected') }}</th>
                                <th class="px-4 py-3 text-right font-semibold text-blue-800">{{ __('Not connected') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-50/80 bg-white">
                            @forelse ($dailyStats ?? [] as $day)
                                <tr class="hover:bg-blue-50/30 transition">
                                    <td class="px-4 py-3 text-slate-700 font-medium">{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-blue-800">{{ $day['total'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-600">{{ $day['connected'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-rose-600">{{ $day['not_connected'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-sm text-slate-500 text-center">{{ __('No calls in this period.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Students under this telecaller --}}
            @php
                $uncalledStudentIds = $students->filter(fn ($s) => empty($callCountsByStudent[$s->id] ?? 0))->pluck('id')->all();
                $uncalledCountOnPage = count($uncalledStudentIds);
            @endphp
            <div class="bg-blue-50 rounded-2xl shadow-lg shadow-blue-100/50 border border-blue-200 p-5" x-data="{ selectedIds: [], selectAll: false }">
                <div class="flex items-center gap-2 mb-5 rounded-xl bg-blue-100/70 border border-blue-200/70 px-4 py-3">
                    <span class="flex h-8 w-1 rounded-full bg-blue-600"></span>
                    <div>
                        <p class="text-sm font-semibold text-blue-900">{{ __('Students under this telecaller') }}</p>
                        <p class="text-xs text-slate-500">{{ $students->total() }} {{ __('assigned') }} · {{ $totalUncalled ?? 0 }} {{ __('not called (revocable)') }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2 mb-4">
                    @if (($totalUncalled ?? 0) > 0)
                        <form method="POST" action="{{ route('admin.staff.revoke-students', $staff) }}" id="revokeForm"
                              onsubmit="return confirm('{{ __('Revoke selected students? They will become unassigned.') }}')">
                            @csrf
                            <template x-for="id in selectedIds" :key="id">
                                <input type="hidden" name="student_ids[]" :value="id">
                            </template>
                            <button type="submit" x-show="selectedIds.length > 0" x-cloak
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-rose-500 hover:bg-rose-600 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                {{ __('Revoke selected') }} (<span x-text="selectedIds.length"></span>)
                            </button>
                        </form>
                        <button type="button" @click="selectAll = !selectAll; selectedIds = selectAll ? {{ json_encode($uncalledStudentIdsAll) }} : []"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition">
                            <span x-text="selectAll ? '{{ __('Deselect all') }}' : '{{ __('Select all uncalled (all pages)') }}'"></span>
                        </button>
                    @endif
                    @if (($totalUncalled ?? 0) > 0 && $selectedSchoolId > 0 && $selectedClassSectionId > 0)
                        <form method="POST" action="{{ route('admin.staff.revoke-students', $staff) }}"
                              onsubmit="return confirm('{{ __('Revoke ALL uncalled students for this School/Class (all pages)? This cannot be undone.') }}')">
                            @csrf
                            <input type="hidden" name="select_all_filtered" value="1">
                            @if ($filterLeadStatus)
                                <input type="hidden" name="lead_status" value="{{ $filterLeadStatus }}">
                            @endif
                            @if ($selectedSchoolId > 0)
                                <input type="hidden" name="school_id" value="{{ $selectedSchoolId }}">
                            @endif
                            @if ($selectedClassSectionId > 0)
                                <input type="hidden" name="class_section_id" value="{{ $selectedClassSectionId }}">
                            @endif
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 transition">
                                {{ __('Revoke ALL uncalled (all pages)') }}
                            </button>
                        </form>
                    @endif
                </div>
                <div class="overflow-x-auto rounded-xl border border-blue-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-blue-100 text-sm">
                        <thead class="bg-gradient-to-r from-blue-50 to-sky-50">
                            <tr>
                                @if ($uncalledCountOnPage > 0)
                                    <th class="px-3 py-3 w-8"></th>
                                @endif
                                <th class="px-4 py-3 text-left font-semibold text-blue-800">{{ __('Student') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-800">{{ __('School / Class') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-800">{{ __('Phone') }}</th>
                                <th class="px-4 py-3 text-center font-semibold text-blue-800">{{ __('Calls') }}</th>
                                <th class="px-4 py-3 text-center font-semibold text-blue-800">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-50/80 bg-white">
                            @forelse ($students as $student)
                                @php
                                    $staffCalls = $callCountsByStudent[$student->id] ?? 0;
                                    $canRevoke = $staffCalls === 0;
                                @endphp
                                <tr class="hover:bg-blue-50/30 transition {{ $canRevoke ? 'bg-amber-50/50' : '' }}">
                                    @if ($uncalledCountOnPage > 0)
                                        <td class="px-3 py-3 text-center">
                                            @if ($canRevoke)
                                                <input type="checkbox" value="{{ $student->id }}"
                                                       class="rounded border-blue-300 text-blue-600 focus:ring-blue-500"
                                                       :checked="selectedIds.includes({{ $student->id }})"
                                                       @change="$event.target.checked ? (selectedIds = [...selectedIds, {{ $student->id }}]) : (selectedIds = selectedIds.filter(i => i !== {{ $student->id }}))">
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-4 py-3">
                                        <a href="{{ route('students.show', $student) }}"
                                           class="font-medium text-blue-700 hover:text-blue-900 hover:underline">
                                            {{ $student->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $student->classSection?->school?->name ?? '—' }}
                                        @if ($student->classSection)
                                            · {{ $student->classSection->full_name }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $student->whatsapp_phone_primary ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($staffCalls > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">{{ $staffCalls }}</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-700">0</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $leadStatusLabel = ($leadStatusOptions ?? [])[$student->lead_status ?? 'lead'] ?? ucfirst(str_replace('_', ' ', $student->lead_status ?? 'lead'));
                                        @endphp
                                        @if ($canRevoke)
                                            <span class="text-[11px] text-amber-700 font-medium">{{ __('No calls') }}</span>
                                        @else
                                            <span class="text-[11px] text-blue-700 font-medium">{{ $leadStatusLabel }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-sm text-slate-500 text-center">
                                        {{ __('No students currently assigned.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($students->hasPages())
                    <div class="px-4 py-3 border-t border-blue-100 bg-blue-50/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm">
                        <p class="text-slate-600">
                            {{ __('Showing') }} <span class="font-semibold">{{ $students->firstItem() ?? 0 }}</span> {{ __('to') }} <span class="font-semibold">{{ $students->lastItem() ?? 0 }}</span> {{ __('of') }} <span class="font-semibold">{{ $students->total() }}</span> {{ __('students') }}
                        </p>
                        <div>{{ $students->links() }}</div>
                    </div>
                @endif
            </div>

            {{-- Recent calls and campaigns --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="bg-sky-50 rounded-2xl shadow-lg shadow-sky-100/60 border border-sky-200 p-5">
                    <div class="flex items-center gap-2 mb-5 rounded-xl bg-sky-100/70 border border-sky-200/70 px-4 py-3">
                        <span class="flex h-8 w-1 rounded-full bg-sky-600"></span>
                        <p class="text-sm font-semibold text-sky-900">{{ __('Recent calls') }}</p>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-blue-100 overflow-hidden">
                        <table class="min-w-full divide-y divide-blue-100 text-sm">
                            <thead class="bg-gradient-to-r from-blue-50 to-sky-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-blue-800">{{ __('When') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-blue-800">{{ __('Student') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-blue-800">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-50/80 bg-white">
                                @forelse ($recentCalls as $call)
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <td class="px-4 py-3 text-xs text-slate-600">
                                            {{ $call->called_at?->format('d M, H:i') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($call->student)
                                                <a href="{{ route('students.show', $call->student) }}"
                                                   class="font-medium text-blue-700 hover:text-blue-900 hover:underline">
                                                    {{ $call->student->name }}
                                                </a>
                                            @else
                                                <span class="text-slate-500">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs font-medium text-slate-700">
                                            {{ ucfirst(str_replace('_', ' ', $call->call_status)) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-sm text-slate-500 text-center">
                                            {{ __('No calls yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($recentCalls->hasPages())
                        <div class="px-4 py-3 border-t border-blue-100 bg-blue-50/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm">
                            <p class="text-slate-600">
                                {{ __('Showing') }} <span class="font-semibold">{{ $recentCalls->firstItem() ?? 0 }}</span> {{ __('to') }} <span class="font-semibold">{{ $recentCalls->lastItem() ?? 0 }}</span> {{ __('of') }} <span class="font-semibold">{{ $recentCalls->total() }}</span> {{ __('calls') }}
                            </p>
                            <div>{{ $recentCalls->links() }}</div>
                        </div>
                    @endif
                </div>

                <div class="bg-indigo-50 rounded-2xl shadow-lg shadow-indigo-100/60 border border-indigo-200 p-5">
                    <div class="flex items-center gap-2 mb-5 rounded-xl bg-indigo-100/70 border border-indigo-200/70 px-4 py-3">
                        <span class="flex h-8 w-1 rounded-full bg-indigo-600"></span>
                        <p class="text-sm font-semibold text-indigo-900">{{ __('Campaigns shot by this telecaller') }}</p>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-blue-100 overflow-hidden">
                        <table class="min-w-full divide-y divide-blue-100 text-sm">
                            <thead class="bg-gradient-to-r from-blue-50 to-sky-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-blue-800">{{ __('Name') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-blue-800">{{ __('School') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-blue-800">{{ __('Created at') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-50/80 bg-white">
                                @forelse ($campaigns as $campaign)
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('campaigns.show', $campaign) }}"
                                               class="font-medium text-blue-700 hover:text-blue-900 hover:underline">
                                                {{ $campaign->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            {{ $campaign->school?->name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-slate-600">
                                            {{ $campaign->created_at?->format('d M Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-sm text-slate-500 text-center">
                                            {{ __('No campaigns shot by this telecaller yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($campaigns->hasPages())
                        <div class="px-4 py-3 border-t border-blue-100 bg-blue-50/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm">
                            <p class="text-slate-600">
                                {{ __('Showing') }} <span class="font-semibold">{{ $campaigns->firstItem() ?? 0 }}</span> {{ __('to') }} <span class="font-semibold">{{ $campaigns->lastItem() ?? 0 }}</span> {{ __('of') }} <span class="font-semibold">{{ $campaigns->total() }}</span> {{ __('campaigns') }}
                            </p>
                            <div>{{ $campaigns->links() }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

