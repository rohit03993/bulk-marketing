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
            {{-- Filters --}}
            <div class="bg-white rounded-2xl shadow-lg shadow-blue-100/50 border border-blue-100 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="flex h-8 w-1 rounded-full bg-blue-500"></span>
                    <p class="text-sm font-semibold text-blue-900">{{ __('Filter data') }}</p>
                </div>
                <form method="get" action="{{ route('admin.staff.show', $staff) }}" class="flex flex-wrap items-end gap-4">
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
                    <div>
                        <label for="lead_status" class="block text-xs font-medium text-blue-700">{{ __('Lead status') }}</label>
                        <select id="lead_status" name="lead_status" class="mt-1 block w-full rounded-lg border-blue-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 text-slate-700">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($leadStatusOptions ?? [] as $value => $label)
                                <option value="{{ $value }}" {{ ($filterLeadStatus ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="school_id" class="block text-xs font-medium text-blue-700">{{ __('School') }}</label>
                        <select id="school_id" name="school_id" class="mt-1 block w-full rounded-lg border-blue-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 text-slate-700">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($schools ?? [] as $school)
                                <option value="{{ $school->id }}" {{ (request('school_id') == $school->id) ? 'selected' : '' }}>
                                    {{ $school->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="class_section_id" class="block text-xs font-medium text-blue-700">{{ __('Class / Section') }}</label>
                        <select id="class_section_id" name="class_section_id" class="mt-1 block w-full rounded-lg border-blue-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 text-slate-700">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($classSections ?? [] as $cs)
                                <option value="{{ $cs->id }}" {{ (request('class_section_id') == $cs->id) ? 'selected' : '' }}>
                                    {{ $cs->full_name ?? ($cs->class_name . ' ' . $cs->section_name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-200/50 transition">
                        {{ __('Apply') }}
                    </button>
                    <a href="{{ route('admin.staff.show', $staff) }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition">
                        {{ __('Clear') }}
                    </a>
                </form>
            </div>

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
                    <p class="text-xs font-medium text-blue-600">{{ __('Students assigned') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-blue-700">
                        {{ $assignedTotal ?? $students->total() }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ __('Converted:') }}
                        <span class="font-semibold text-emerald-700">
                            {{ ($convertedWalkin + $convertedAdmission) }}
                        </span>
                        · {{ __('Exited:') }}
                        <span class="font-semibold text-rose-700">
                            {{ $exitedNotInterested }}
                        </span>
                    </p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg shadow-indigo-100/40 border border-indigo-100 p-5 overflow-hidden relative">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-indigo-400/20 to-transparent rounded-bl-full"></div>
                    <p class="text-xs font-medium text-indigo-600">{{ __('Converted') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-indigo-700">
                        {{ ($convertedWalkin + $convertedAdmission) }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ __('Walk-ins:') }} <span class="font-semibold text-slate-700">{{ $convertedWalkin }}</span>
                        · {{ __('Admissions:') }} <span class="font-semibold text-slate-700">{{ $convertedAdmission }}</span>
                    </p>
                </div>
            </div>

            {{-- Calls summary (all time + optional range) --}}
            <div class="bg-white rounded-2xl shadow-lg shadow-blue-100/50 border border-blue-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-1 rounded-full bg-blue-500"></span>
                        <p class="text-sm font-semibold text-blue-900">{{ __('Call activity summary') }}</p>
                    </div>
                    <a href="{{ route('calls.report', ['staff_id' => $staff->id]) }}"
                       class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                        {{ __('Open full call report') }} →
                    </a>
                </div>
                <p class="text-xs font-medium text-blue-700 mb-3">{{ __('Total calls till now (all time)') }}</p>
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
                    <p class="text-xs font-medium text-blue-700 mt-4 mb-2">{{ __('In selected date range') }}</p>
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

            {{-- Daily breakdown --}}
            <div class="bg-white rounded-2xl shadow-lg shadow-blue-100/50 border border-blue-100 p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="flex h-8 w-1 rounded-full bg-blue-500"></span>
                    <p class="text-sm font-semibold text-blue-900">{{ __('Daily breakdown') }}</p>
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
            <div class="bg-white rounded-2xl shadow-lg shadow-blue-100/50 border border-blue-100 p-5" x-data="{ selectedIds: [], selectAll: false }">
                <div class="flex items-center gap-2 mb-4">
                    <span class="flex h-8 w-1 rounded-full bg-blue-500"></span>
                    <div>
                        <p class="text-sm font-semibold text-blue-900">{{ __('Students under this telecaller') }}</p>
                        <p class="text-xs text-slate-500">{{ $students->total() }} {{ __('assigned') }} · {{ $totalUncalled ?? 0 }} {{ __('not called (revocable)') }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2 mb-4">
                    @if ($uncalledCountOnPage > 0)
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
                        <button type="button" @click="selectAll = !selectAll; selectedIds = selectAll ? {{ json_encode($uncalledStudentIds) }} : []"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition">
                            <span x-text="selectAll ? '{{ __('Deselect all') }}' : '{{ __('Select all uncalled') }}'"></span>
                        </button>
                    @endif
                    @if (($totalUncalled ?? 0) > 0 && request('school_id') && request('class_section_id'))
                        <form method="POST" action="{{ route('admin.staff.revoke-students', $staff) }}"
                              onsubmit="return confirm('{{ __('Revoke ALL uncalled students for this School/Class (all pages)? This cannot be undone.') }}')">
                            @csrf
                            <input type="hidden" name="select_all_filtered" value="1">
                            @if ($filterLeadStatus)
                                <input type="hidden" name="lead_status" value="{{ $filterLeadStatus }}">
                            @endif
                            @if (request('school_id'))
                                <input type="hidden" name="school_id" value="{{ request('school_id') }}">
                            @endif
                            @if (request('class_section_id'))
                                <input type="hidden" name="class_section_id" value="{{ request('class_section_id') }}">
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
                                                       @change="$event.target.checked ? selectedIds.push({{ $student->id }}) : selectedIds = selectedIds.filter(i => i !== {{ $student->id }})">
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
                <div class="bg-white rounded-2xl shadow-lg shadow-blue-100/50 border border-blue-100 p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="flex h-8 w-1 rounded-full bg-blue-500"></span>
                        <p class="text-sm font-semibold text-blue-900">{{ __('Recent calls') }}</p>
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

                <div class="bg-white rounded-2xl shadow-lg shadow-blue-100/50 border border-blue-100 p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="flex h-8 w-1 rounded-full bg-blue-500"></span>
                        <p class="text-sm font-semibold text-blue-900">{{ __('Campaigns shot by this telecaller') }}</p>
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

