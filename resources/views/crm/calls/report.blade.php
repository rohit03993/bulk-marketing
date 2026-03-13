<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ __('Call report') }}</h2>
                <div class="mt-1 text-xs text-slate-500">
                    {{ __('Filter connected / not connected calls by date range.') }}
                </div>
            </div>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center px-3 py-2 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 bg-white hover:bg-slate-50">
                {{ __('Back to dashboard') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                    <div class="text-xs text-slate-500">{{ __('Total calls') }}</div>
                    <div class="mt-1 text-2xl font-bold text-slate-900">{{ $summary['total'] ?? 0 }}</div>
                </div>
                <div class="bg-white rounded-2xl border border-emerald-200 shadow-sm p-4">
                    <div class="text-xs text-slate-500">{{ __('Connected') }}</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-700">{{ $summary['connected'] ?? 0 }}</div>
                </div>
                <div class="bg-white rounded-2xl border border-rose-200 shadow-sm p-4">
                    <div class="text-xs text-slate-500">{{ __('Not connected') }}</div>
                    <div class="mt-1 text-2xl font-bold text-rose-700">{{ $summary['not_connected'] ?? 0 }}</div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                <form method="GET" action="{{ route('calls.report') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    @if ($isAdmin)
                        <div class="md:col-span-3">
                            <label class="block text-xs font-medium text-slate-600">{{ __('Telecaller') }}</label>
                            <select name="staff_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm">
                                @foreach ($staffOptions as $u)
                                    <option value="{{ $u->id }}" {{ (string) ($filters['staff_id'] ?? '') === (string) $u->id ? 'selected' : '' }}>
                                        {{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600">{{ __('From') }}</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600">{{ __('To') }}</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600">{{ __('Connection') }}</label>
                        <select name="connection" class="mt-1 block w-full rounded-xl border-slate-200 text-sm">
                            <option value="all" {{ ($filters['connection'] ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('All') }}</option>
                            <option value="connected" {{ ($filters['connection'] ?? '') === 'connected' ? 'selected' : '' }}>{{ __('Connected') }}</option>
                            <option value="not_connected" {{ ($filters['connection'] ?? '') === 'not_connected' ? 'selected' : '' }}>{{ __('Not connected') }}</option>
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-slate-600">{{ __('Reason (not connected)') }}</label>
                        <select name="reason" class="mt-1 block w-full rounded-xl border-slate-200 text-sm">
                            <option value="">{{ __('Any') }}</option>
                            @foreach ($notConnectedStatuses as $s)
                                <option value="{{ $s }}" {{ (string) ($filters['reason'] ?? '') === (string) $s ? 'selected' : '' }}>
                                    {{ \App\Models\StudentCall::$callStatuses[$s] ?? ucfirst(str_replace('_',' ',$s)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-slate-600">{{ __('Lead status changed to') }}</label>
                        @php
                            $leadStatuses = [
                                'lead' => __('Uncalled'),
                                'interested' => __('Interested'),
                                'not_interested' => __('Not Interested'),
                                'walkin_done' => __('Walk-in Done'),
                                'admission_done' => __('Admission Done'),
                                'follow_up_later' => __('Follow-up Later'),
                            ];
                        @endphp
                        <select name="status_changed_to" class="mt-1 block w-full rounded-xl border-slate-200 text-sm">
                            <option value="">{{ __('Any') }}</option>
                            @foreach ($leadStatuses as $v => $lbl)
                                <option value="{{ $v }}" {{ (string) ($filters['status_changed_to'] ?? '') === (string) $v ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-xs font-medium text-slate-600">{{ __('Search student name / phone') }}</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search…') }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 text-sm">
                    </div>

                    <div class="md:col-span-2 flex gap-2">
                        <button type="submit" class="inline-flex justify-center px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                            {{ __('Apply') }}
                        </button>
                        <a href="{{ route('calls.report') }}" class="inline-flex justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('When') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('Student') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('Lead status') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('Notes') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse ($calls as $c)
                                @php
                                    $status = $c->call_status;
                                    $isConnected = $status === \App\Models\StudentCall::STATUS_CONNECTED;
                                    $badge = $isConnected ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800';
                                @endphp
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">
                                        <div>{{ $c->called_at?->format('d M Y, h:i A') ?? '—' }}</div>
                                        @if ($c->call_direction === 'incoming')
                                            <span class="inline-flex items-center gap-0.5 mt-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">↓ {{ __('Incoming') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($c->student)
                                            <a href="{{ route('students.show', $c->student) }}#calls" class="font-semibold text-slate-900 hover:text-indigo-700">
                                                {{ $c->student->name }}
                                            </a>
                                            <div class="text-xs text-slate-500">
                                                {{ $c->student->classSection?->school?->name ?? '—' }}
                                                @if ($c->student->classSection) · {{ $c->student->classSection->full_name }} @endif
                                            </div>
                                        @else
                                            <span class="text-slate-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $badge }}">
                                            {{ \App\Models\StudentCall::$callStatuses[$status] ?? ucfirst(str_replace('_',' ', $status ?? '')) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">
                                        {{ $c->status_changed_to ? ($leadStatuses[$c->status_changed_to] ?? ucfirst(str_replace('_',' ', $c->status_changed_to))) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ $c->call_notes ? \Illuminate\Support\Str::limit($c->call_notes, 140) : '—' }}
                                        @if ($c->next_followup_at)
                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ __('Follow-up:') }} {{ $c->next_followup_at?->format('d M Y') }}
                                            </div>
                                        @endif
                                        @if ($c->whatsapp_auto_status === 'success')
                                            <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-800">✓ {{ __('WA sent') }}</span>
                                        @elseif ($c->whatsapp_auto_status === 'queued')
                                            <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-700">⏳ {{ __('WA queued') }}</span>
                                        @elseif ($c->whatsapp_auto_status === 'failed')
                                            <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-700">✗ {{ __('WA failed') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                        {{ __('No calls found for the selected filters.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-4 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-sm text-slate-600">
                        {{ __('Showing') }}
                        <span class="font-semibold text-slate-800">{{ $calls->firstItem() ?? 0 }}</span>
                        {{ __('to') }}
                        <span class="font-semibold text-slate-800">{{ $calls->lastItem() ?? 0 }}</span>
                        {{ __('of') }}
                        <span class="font-semibold text-slate-800">{{ $calls->total() }}</span>
                        {{ __('calls') }}
                    </p>
                    <div class="flex flex-wrap justify-end">
                        {{ $calls->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

