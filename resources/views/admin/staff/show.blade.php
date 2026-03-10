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
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-slate-700">{{ __('Students under this telecaller') }}</p>
                    <a href="{{ route('students.index') }}"
                       class="text-xs font-semibold text-slate-500 hover:text-slate-700">
                        {{ __('Open all students') }}
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Student') }}</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('School / Class') }}</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">{{ __('Phone') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($students as $student)
                                <tr>
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-4 text-xs text-slate-500 text-center">
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

