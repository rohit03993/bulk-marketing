<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-xl text-slate-900 leading-tight">{{ __('Admin Reports') }}</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ __('Academic session') }}: <span class="font-semibold text-slate-700">{{ $currentSessionName }}</span></p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.staff.index') }}" class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition border border-slate-200">
                    {{ __('Staff') }}
                </a>
                <a href="{{ route('admin.lead-class-presets.index') }}" class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition border border-slate-200">
                    {{ __('Lead Class Presets') }}
                </a>
                <a href="{{ route('admin.settings.postcall-whatsapp') }}" class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition border border-slate-200">
                    {{ __('Auto WhatsApp') }}
                </a>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-semibold text-slate-500 bg-white hover:bg-slate-50 transition border border-slate-200">
                    ← {{ __('Back') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            {{-- KPI cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('Total students') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $kpi['total_students'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-emerald-200 shadow-sm p-5">
                    <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wide">{{ __('Converted') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-emerald-700">{{ $kpi['converted'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-amber-200 shadow-sm p-5">
                    <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide">{{ __('Follow-ups due') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-amber-700">{{ $kpi['followups_due'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-rose-200 shadow-sm p-5">
                    <p class="text-xs font-semibold text-rose-700 uppercase tracking-wide">{{ __('Blocked leads') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-rose-700">{{ $kpi['blocked'] ?? 0 }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- School -> Class breakdown --}}
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ __('School -> Class snapshot') }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ __('Converted = walk-in done + admission done. Follow-ups due are interested/follow-up-later with next_followup_at <= today.') }}</p>
                            </div>
                            <div class="hidden sm:block text-xs text-slate-500">
                                {{ __('Expanded to view classes') }}
                            </div>
                        </div>

                        <div class="p-3 sm:p-5">
                            @if ($schoolBreakdown->isEmpty())
                                <div class="p-8 text-center text-sm text-slate-500">{{ __('No data for this session yet.') }}</div>
                            @else
                                <div class="space-y-3">
                                    @foreach ($schoolBreakdown as $school)
                                        <details class="group rounded-xl border border-slate-200 bg-white overflow-hidden">
                                            <summary class="cursor-pointer list-none px-4 py-3 sm:px-5 sm:py-4 flex items-center justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-slate-900 truncate">
                                                        {{ $school->school_name }}
                                                    </p>
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
                                                    @if (($school->blocked_count ?? 0) > 0)
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-800">
                                                            {{ __('Blocked') }}: {{ $school->blocked_count }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </summary>

                                            <div class="px-4 pb-4 sm:px-5 sm:pb-5">
                                                @php
                                                    $classes = $school->classes ?? collect();
                                                @endphp
                                                @if ($classes->isEmpty())
                                                    <div class="p-4 text-center text-xs text-slate-500">{{ __('No class data for this school in current session.') }}</div>
                                                @else
                                                    <div class="overflow-x-auto">
                                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                                            <thead class="bg-slate-50">
                                                                <tr>
                                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">{{ __('Class / Section') }}</th>
                                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600">{{ __('Students') }}</th>
                                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700">{{ __('Converted') }}</th>
                                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-amber-700">{{ __('Follow-ups due') }}</th>
                                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-rose-700">{{ __('Blocked') }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                                @foreach ($classes as $cls)
                                                                    @php
                                                                        $label = $cls->section_name ? ($cls->class_name . ' - ' . $cls->section_name) : $cls->class_name;
                                                                    @endphp
                                                                    <tr class="hover:bg-slate-50/60">
                                                                        <td class="px-4 py-3 text-slate-800 font-medium">{{ $label }}</td>
                                                                        <td class="px-4 py-3 text-right text-slate-700">{{ $cls->total_students }}</td>
                                                                        <td class="px-4 py-3 text-right text-emerald-700 font-semibold">{{ $cls->converted_count }}</td>
                                                                        <td class="px-4 py-3 text-right text-amber-700 font-semibold">{{ $cls->followups_due_count }}</td>
                                                                        <td class="px-4 py-3 text-right text-rose-700 font-semibold">{{ $cls->blocked_count }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Telecaller breakdown --}}
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100">
                            <p class="text-sm font-semibold text-slate-900">{{ __('Follow-ups due by telecaller') }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ __('Overall within current session') }}</p>
                        </div>

                        @if ($telecallerAggs->isEmpty())
                            <div class="p-8 text-center text-sm text-slate-500">{{ __('No telecaller data found for this session.') }}</div>
                        @else
                            <div class="p-3 sm:p-5">
                                <div class="hidden lg:block overflow-x-auto rounded-xl border border-slate-200">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">{{ __('Telecaller') }}</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600">{{ __('Assigned students') }}</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700">{{ __('Converted') }}</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-amber-700">{{ __('Follow-ups due') }}</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-rose-700">{{ __('Blocked') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @foreach ($telecallerAggs as $t)
                                                <tr class="hover:bg-slate-50/60">
                                                    <td class="px-4 py-3 text-slate-800 font-medium">{{ $t->telecaller_name }}</td>
                                                    <td class="px-4 py-3 text-right text-slate-700">{{ $t->assigned_students_count }}</td>
                                                    <td class="px-4 py-3 text-right text-emerald-700 font-semibold">{{ $t->converted_count }}</td>
                                                    <td class="px-4 py-3 text-right text-amber-700 font-semibold">{{ $t->followups_due_count }}</td>
                                                    <td class="px-4 py-3 text-right text-rose-700 font-semibold">{{ $t->blocked_count }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="lg:hidden space-y-3">
                                    @foreach ($telecallerAggs as $t)
                                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-slate-900 truncate">{{ $t->telecaller_name }}</p>
                                                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Assigned') }}: {{ $t->assigned_students_count }}</p>
                                                </div>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 shrink-0">
                                                    {{ __('Due') }}: {{ $t->followups_due_count }}
                                                </span>
                                            </div>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800">
                                                    {{ __('Converted') }}: {{ $t->converted_count }}
                                                </span>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-800">
                                                    {{ __('Blocked') }}: {{ $t->blocked_count }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-5">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Quick navigation') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Open detailed pages when needed (read-only dashboards only).') }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('schools.index') }}" class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 transition border border-slate-200">{{ __('Schools') }}</a>
                            <a href="{{ route('class-sections.index') }}" class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 transition border border-slate-200">{{ __('Classes') }}</a>
                            <a href="{{ route('students.index') }}" class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 transition border border-slate-200">{{ __('Students') }}</a>
                            <a href="{{ route('admin.staff.show', $firstTelecallerId ?? 0) }}"
                               class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 transition border border-slate-200 {{ ($telecallerAggs->isEmpty()) ? 'pointer-events-none opacity-50' : '' }}">
                                {{ __('Telecaller performance') }}
                            </a>
                            <a href="{{ route('calls.report') }}" class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 transition border border-slate-200">{{ __('Call report') }}</a>
                </div>
            </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
