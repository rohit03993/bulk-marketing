<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                    {{ $staff->name }}
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    {{ __('Telecaller performance overview') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.staff.index') }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200">
                    ← {{ __('Back to Staff') }}
                </a>
                <a href="{{ route('admin.staff.edit', $staff) }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-slate-800 hover:bg-slate-700">
                    {{ __('Edit staff') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Top summary cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-5">
                    <p class="text-xs font-medium text-slate-500">{{ __('My score (today)') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-indigo-700">
                        {{ $scoreToday['score'] ?? 0 }}%
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ __('Calls:') }}
                        <span class="font-semibold text-slate-800">
                            {{ $scoreToday['breakdown']['total_calls'] ?? 0 }}/{{ $scoreToday['breakdown']['daily_target'] ?? 25 }}
                        </span>
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <p class="text-xs font-medium text-slate-500">{{ __('Overall average (working days)') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-800">
                        {{ $scoreOverall['score'] ?? 0 }}%
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ __('Working days:') }} {{ $scoreOverall['days'] ?? 0 }}
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-emerald-100 shadow-sm p-5">
                    <p class="text-xs font-medium text-slate-500">{{ __('Students assigned') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-emerald-700">
                        {{ $students->count() }}
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-5">
                    <p class="text-xs font-medium text-slate-500">{{ __('Messages sent (campaigns)') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-amber-700">
                        {{ $messagesSent }}
                    </p>
                </div>
            </div>

            {{-- Calls summary --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-700">{{ __('Call activity summary') }}</p>
                    <a href="{{ route('calls.report', ['staff_id' => $staff->id]) }}"
                       class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        {{ __('Open full call report') }}
                    </a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-slate-500">{{ __('Total calls') }}</p>
                        <p class="mt-1 text-xl font-semibold text-slate-800">
                            {{ $callsSummary['total'] ?? 0 }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">{{ __('Connected') }}</p>
                        <p class="mt-1 text-xl font-semibold text-emerald-700">
                            {{ $callsSummary['connected'] ?? 0 }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">{{ __('Not connected') }}</p>
                        <p class="mt-1 text-xl font-semibold text-rose-600">
                            {{ $callsSummary['not_connected'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Students under this telecaller --}}
            @php
                $uncalledStudentIds = $students->filter(fn ($s) => empty($callCountsByStudent[$s->id] ?? 0))->pluck('id')->all();
                $uncalledCount = count($uncalledStudentIds);
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5" x-data="{ selectedIds: [], selectAll: false }">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3">
                    <div>
                        <p class="text-sm font-medium text-slate-700">{{ __('Students under this telecaller') }}</p>
                        <p class="text-xs text-slate-500">{{ $students->count() }} {{ __('assigned') }} · {{ $uncalledCount }} {{ __('not called (revocable)') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($uncalledCount > 0)
                            <form method="POST" action="{{ route('admin.staff.revoke-students', $staff) }}" id="revokeForm"
                                  onsubmit="return confirm('{{ __('Revoke selected students? They will become unassigned.') }}')">
                                @csrf
                                <template x-for="id in selectedIds" :key="id">
                                    <input type="hidden" name="student_ids[]" :value="id">
                                </template>
                                <button type="submit" x-show="selectedIds.length > 0" x-cloak
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    {{ __('Revoke selected') }} (<span x-text="selectedIds.length"></span>)
                                </button>
                            </form>
                            <button type="button" @click="selectAll = !selectAll; selectedIds = selectAll ? {{ json_encode($uncalledStudentIds) }} : []"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                                <span x-text="selectAll ? '{{ __('Deselect all') }}' : '{{ __('Select all uncalled') }}'"></span>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                @if ($uncalledCount > 0)
                                    <th class="px-2 py-2 w-8"></th>
                                @endif
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Student') }}</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('School / Class') }}</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Phone') }}</th>
                                <th class="px-3 py-2 text-center font-semibold text-slate-500">{{ __('Calls') }}</th>
                                <th class="px-3 py-2 text-center font-semibold text-slate-500">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($students as $student)
                                @php
                                    $staffCalls = $callCountsByStudent[$student->id] ?? 0;
                                    $canRevoke = $staffCalls === 0;
                                @endphp
                                <tr class="{{ $canRevoke ? 'bg-amber-50/40' : '' }}">
                                    @if ($uncalledCount > 0)
                                        <td class="px-2 py-2 text-center">
                                            @if ($canRevoke)
                                                <input type="checkbox" value="{{ $student->id }}"
                                                       class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                                       @change="$event.target.checked ? selectedIds.push({{ $student->id }}) : selectedIds = selectedIds.filter(i => i !== {{ $student->id }})">
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-3 py-2">
                                        <a href="{{ route('students.show', $student) }}"
                                           class="text-slate-800 font-medium hover:text-indigo-600">
                                            {{ $student->name }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">
                                        {{ $student->classSection?->school?->name ?? '—' }}
                                        @if ($student->classSection)
                                            · {{ $student->classSection->full_name }}
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">
                                        {{ $student->whatsapp_phone_primary ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($staffCalls > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">{{ $staffCalls }}</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-100 text-red-700">0</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($canRevoke)
                                            <span class="text-[11px] text-amber-700 font-medium">{{ __('No calls') }}</span>
                                        @else
                                            <span class="text-[11px] text-emerald-700 font-medium">{{ __('Active') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-4 text-xs text-slate-500 text-center">
                                        {{ __('No students currently assigned.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recent calls and campaigns --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <p class="text-sm font-medium text-slate-700 mb-3">{{ __('Recent calls') }}</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('When') }}</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Student') }}</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($recentCalls as $call)
                                    <tr>
                                        <td class="px-3 py-2 text-xs text-slate-500">
                                            {{ $call->called_at?->format('d M, H:i') ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            @if ($call->student)
                                                <a href="{{ route('students.show', $call->student) }}"
                                                   class="text-slate-800 font-medium hover:text-indigo-600">
                                                    {{ $call->student->name }}
                                                </a>
                                            @else
                                                <span class="text-slate-500">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-xs text-slate-600">
                                            {{ ucfirst(str_replace('_', ' ', $call->call_status)) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-3 py-4 text-xs text-slate-500 text-center">
                                            {{ __('No calls yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <p class="text-sm font-medium text-slate-700 mb-3">{{ __('Campaigns shot by this telecaller') }}</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Name') }}</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('School') }}</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Created at') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($campaigns as $campaign)
                                    <tr>
                                        <td class="px-3 py-2">
                                            <a href="{{ route('campaigns.show', $campaign) }}"
                                               class="text-slate-800 font-medium hover:text-indigo-600">
                                                {{ $campaign->name }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-2 text-slate-600">
                                            {{ $campaign->school?->name ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-xs text-slate-500">
                                            {{ $campaign->created_at?->format('d M Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-3 py-4 text-xs text-slate-500 text-center">
                                            {{ __('No campaigns shot by this telecaller yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

