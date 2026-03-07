<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @php $stats = $stats ?? []; @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                <a href="{{ route('schools.index') }}" class="group bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-blue-200 transition-all duration-200 p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Schools') }}</p>
                            <p class="mt-2 text-3xl font-bold text-slate-800">{{ $stats['schools'] ?? 0 }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-100 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                    </div>
                </a>
                <a href="{{ route('students.index') }}" class="group bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-blue-200 transition-all duration-200 p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Students') }}</p>
                            <p class="mt-2 text-3xl font-bold text-slate-800">{{ $stats['students'] ?? 0 }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center group-hover:bg-slate-200 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                    </div>
                </a>
                <a href="{{ route('campaigns.index') }}" class="group bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-blue-200 transition-all duration-200 p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Campaigns') }}</p>
                            <p class="mt-2 text-3xl font-bold text-slate-800">{{ $stats['campaigns'] ?? 0 }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center group-hover:bg-slate-200 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        </div>
                    </div>
                </a>
                <a href="{{ route('campaigns.index') }}" class="group bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-emerald-200 transition-all duration-200 p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Messages sent') }}</p>
                            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['sent'] ?? 0 }}</p>
                            @if (($stats['pending'] ?? 0) > 0 || ($stats['failed'] ?? 0) > 0)
                                <p class="mt-1.5 text-xs text-slate-500">
                                    @if (($stats['pending'] ?? 0) > 0)<span class="text-amber-600">{{ $stats['pending'] }} {{ __('pending') }}</span>@endif
                                    @if (($stats['pending'] ?? 0) > 0 && ($stats['failed'] ?? 0) > 0)<span class="mx-1">·</span>@endif
                                    @if (($stats['failed'] ?? 0) > 0)<span class="text-red-600">{{ $stats['failed'] }} {{ __('failed') }}</span>@endif
                                </p>
                            @endif
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-100 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </a>
            </div>

            @if (($stats['campaigns_completed'] ?? 0) > 0 || ($stats['campaigns_draft'] ?? 0) > 0 || ($stats['campaigns_in_progress'] ?? 0) > 0)
            <div class="flex flex-wrap gap-2 mb-6">
                @if (($stats['campaigns_completed'] ?? 0) > 0)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-emerald-50 text-emerald-800">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>{{ $stats['campaigns_completed'] }} {{ __('Completed') }}
                    </span>
                @endif
                @if (($stats['campaigns_in_progress'] ?? 0) > 0)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-blue-50 text-blue-800">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>{{ $stats['campaigns_in_progress'] }} {{ __('In progress') }}
                    </span>
                @endif
                @if (($stats['campaigns_draft'] ?? 0) > 0)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-amber-50 text-amber-800">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>{{ $stats['campaigns_draft'] }} {{ __('Draft') }}
                    </span>
                @endif
            </div>
            @endif

            <div class="bg-slate-50 rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-100/80">
                    <h3 class="font-semibold text-slate-800">{{ __('By school') }}</h3>
                    <p class="text-sm text-slate-500 mt-0.5">
                        {{ __('Students, classes and campaigns per school') }}
                        @if (isset($schools) && $schools->isNotEmpty())
                            · <span class="text-slate-600">{{ $schools->count() }} {{ __('schools') }} · {{ number_format($schools->sum('students_count')) }} {{ __('students') }} · {{ $schools->sum('campaigns_count') }} {{ __('campaigns') }}</span>
                        @endif
                    </p>
                </div>
                <div class="overflow-x-auto">
                    @if (isset($schools) && $schools->isNotEmpty())
                        <div class="md:hidden p-4 space-y-3">
                            @foreach ($schools as $school)
                                <a href="{{ route('schools.edit', $school) }}" class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-slate-300 hover:shadow transition">
                                    <p class="font-semibold text-slate-800">{{ $school->name }}@if ($school->short_name) <span class="text-slate-500 font-normal">({{ $school->short_name }})</span>@endif</p>
                                    <div class="mt-3 flex flex-wrap gap-3 text-sm text-slate-600">
                                        <span>{{ $school->students_count }} {{ __('Students') }}</span>
                                        <span>{{ $school->class_sections_count }} {{ __('Classes') }}</span>
                                        <span>{{ $school->campaigns_count }} {{ __('Campaigns') }}</span>
                                    </div>
                                    <p class="mt-2 text-sm font-medium text-indigo-600">{{ __('Edit') }}</p>
                                </a>
                            @endforeach
                        </div>
                        <div class="hidden md:block">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('School') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Students') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Classes') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Campaigns done') }}</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-200">
                                    @foreach ($schools as $school)
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="px-6 py-4">
                                                <span class="font-medium text-slate-800">{{ $school->name }}</span>
                                                @if ($school->short_name)
                                                    <span class="text-slate-500 text-sm">({{ $school->short_name }})</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-600">{{ $school->students_count }}</td>
                                            <td class="px-6 py-4 text-sm text-slate-600">{{ $school->class_sections_count }}</td>
                                            <td class="px-6 py-4 text-sm text-slate-600">{{ $school->campaigns_count }}</td>
                                            <td class="px-6 py-4 text-right">
                                                <a href="{{ route('schools.edit', $school) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">{{ __('Edit') }}</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="px-6 py-8 text-center text-slate-500 text-sm">
                            {{ __('No schools yet.') }}
                            <a href="{{ route('schools.index') }}" class="text-indigo-600 hover:underline ml-1">{{ __('Add a school') }}</a>
                        </div>
                    @endif
                </div>
            </div>

            @if (isset($recentCampaigns) && $recentCampaigns->isNotEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-800">{{ __('Recent campaigns') }}</h3>
                        <p class="text-sm text-slate-500 mt-0.5">{{ __('Last updated campaigns') }}</p>
                    </div>
                    <a href="{{ route('campaigns.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('View all') }}</a>
                </div>
                <div class="overflow-x-auto">
                    <div class="md:hidden p-4 space-y-3">
                        @foreach ($recentCampaigns as $row)
                            <a href="{{ route('campaigns.show', $row->campaign) }}" class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-slate-300 hover:shadow transition">
                                <p class="font-semibold text-slate-800">{{ $row->campaign->name }}</p>
                                <p class="text-sm text-slate-500 mt-0.5">{{ $row->campaign->school->name ?? '—' }}</p>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-medium text-slate-700">{{ $row->sent }} / {{ $row->total }}</span>
                                    @if ($row->campaign->status === 'completed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">{{ __('Completed') }}</span>
                                    @elseif (in_array($row->campaign->status, ['queued', 'running']))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $row->campaign->status }}</span>
                                    @elseif ($row->campaign->status === 'draft')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">{{ __('Draft') }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">{{ $row->campaign->status }}</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-sm font-medium text-indigo-600">{{ __('View') }}</p>
                            </a>
                        @endforeach
                    </div>
                    <div class="hidden md:block">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-2.5 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Campaign') }}</th>
                                    <th class="px-6 py-2.5 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('School') }}</th>
                                    <th class="px-6 py-2.5 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Sent / Total') }}</th>
                                    <th class="px-6 py-2.5 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Status') }}</th>
                                    <th class="px-6 py-2.5 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                @foreach ($recentCampaigns as $row)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-6 py-3 font-medium text-slate-800">{{ $row->campaign->name }}</td>
                                        <td class="px-6 py-3 text-sm text-slate-600">{{ $row->campaign->school->name ?? '—' }}</td>
                                        <td class="px-6 py-3 text-sm text-slate-600">{{ $row->sent }} / {{ $row->total }}</td>
                                        <td class="px-6 py-3">
                                            @if ($row->campaign->status === 'completed')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">{{ __('Completed') }}</span>
                                            @elseif (in_array($row->campaign->status, ['queued', 'running']))
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $row->campaign->status }}</span>
                                            @elseif ($row->campaign->status === 'draft')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">{{ __('Draft') }}</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">{{ $row->campaign->status }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <a href="{{ route('campaigns.show', $row->campaign) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">{{ __('View') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-slate-50 rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-100/80">
                    <h3 class="font-semibold text-slate-800">{{ __('Quick links') }}</h3>
                    <p class="text-sm text-slate-500 mt-0.5">{{ __('Jump to a section') }}</p>
                </div>
                <div class="p-6 flex flex-wrap gap-3">
                    <a href="{{ route('schools.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 hover:text-slate-900 transition">{{ __('Schools') }}</a>
                    <a href="{{ route('sessions.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 hover:text-slate-900 transition">{{ __('Sessions') }}</a>
                    <a href="{{ route('class-sections.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 hover:text-slate-900 transition">{{ __('Classes') }}</a>
                    <a href="{{ route('students.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 hover:text-slate-900 transition">{{ __('Students') }}</a>
                    <a href="{{ route('student-imports.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 hover:text-slate-900 transition">{{ __('Import Excel') }}</a>
                    <a href="{{ route('templates.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 hover:text-slate-900 transition">{{ __('Templates') }}</a>
                    <a href="{{ route('campaigns.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 shadow-sm hover:shadow transition">{{ __('New campaign') }}</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
