<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Students') }}</h2>
            <div class="flex flex-wrap gap-2">
                @if (Auth::user()->isAdmin())
                    <a href="{{ route('students.assign') }}"
                       class="inline-flex items-center px-3 py-2 border border-sky-200 bg-sky-50 text-sky-800 text-xs font-medium rounded-md hover:bg-sky-100">
                        {{ __('Assign leads') }}
                    </a>
                @endif
                @unless (Auth::user()->isAdmin())
                    <a href="{{ route('students.call-queue') }}"
                       class="inline-flex items-center px-3 py-2 border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-medium rounded-md hover:bg-emerald-100">
                        {{ __('Start Calling') }}
                    </a>
                    <a href="{{ route('students.followups') }}"
                       class="inline-flex items-center px-3 py-2 border border-amber-200 bg-amber-50 text-amber-800 text-xs font-medium rounded-md hover:bg-amber-100">
                        {{ __('Follow-ups') }}
                    </a>
                    <a href="{{ route('students.my-leads') }}"
                       class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-50">
                        {{ __('My leads') }}
                    </a>
                @endunless
                <a href="{{ route('student-imports.create') }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    {{ __('Import Excel') }}
                </a>
                <a href="{{ route('students.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    {{ __('Add Student') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('info'))
                <div class="mb-4 rounded-md bg-blue-50 p-4 text-sm text-blue-800">{{ session('info') }}</div>
            @endif

            <form method="GET" action="{{ route('students.index') }}" class="mb-4 space-y-2">
                <div class="flex flex-wrap gap-2 items-end">
                    <div class="min-w-[120px]">
                        <label class="block text-xs font-medium text-gray-500">{{ __('School') }}</label>
                        <select name="school_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($schools as $s)
                                <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[120px]">
                        <label class="block text-xs font-medium text-gray-500">{{ __('Session') }}</label>
                        <select name="session_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($sessions as $s)
                                <option value="{{ $s->id }}" {{ request('session_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[140px]">
                        <label class="block text-xs font-medium text-gray-500">{{ __('Class') }}</label>
                        <select name="class_section_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($classSections as $cs)
                                <option value="{{ $cs->id }}" {{ request('class_section_id') == $cs->id ? 'selected' : '' }}>{{ $cs->full_name }} ({{ $cs->school->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-medium text-gray-500">{{ __('Search name / phone / roll') }}</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search…') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    </div>
                    @if (isset($assignedToOptions) && Auth::user()->isAdmin())
                        <div class="min-w-[160px]">
                            <label class="block text-xs font-medium text-gray-500">{{ __('Assigned to') }}</label>
                            <select name="assigned_to" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="all">{{ __('All') }}</option>
                                <option value="unassigned" {{ request('assigned_to') === 'unassigned' ? 'selected' : '' }}>{{ __('Unassigned') }}</option>
                                @foreach ($assignedToOptions as $u)
                                    <option value="{{ $u->id }}" {{ (string) request('assigned_to') === (string) $u->id ? 'selected' : '' }}>
                                        {{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">{{ __('Filter') }}</button>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($students->isEmpty())
                    <div class="p-6 text-gray-500 text-center">
                        {{ __('No students found.') }}
                        <a href="{{ route('students.create') }}" class="text-indigo-600 hover:underline ml-1">{{ __('Add one') }}</a>
                        {{ __('or') }}
                        <a href="{{ route('student-imports.create') }}" class="text-indigo-600 hover:underline ml-1">{{ __('Import Excel') }}</a>
                    </div>
                @else
                    {{-- Mobile: card view with all columns --}}
                    <div class="md:hidden divide-y divide-gray-200">
                        @foreach ($students as $student)
                            <div class="p-4 hover:bg-gray-50/50">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold text-gray-900">
                                            <a href="{{ route('students.show', $student) }}" class="hover:text-indigo-700">
                                                {{ $student->name }}
                                            </a>
                                        </div>
                                    </div>
                                    <a href="{{ route('students.edit', $student) }}" class="shrink-0 text-indigo-600 text-sm font-medium">{{ __('Edit') }}</a>
                                </div>
                                <dl class="mt-2 grid grid-cols-1 gap-1 text-sm">
                                    @if ($student->father_name)
                                        <div><span class="text-gray-500">{{ __('Father') }}:</span> <span class="text-gray-900">{{ $student->father_name }}</span></div>
                                    @endif
                                    <div><span class="text-gray-500">{{ __('School') }}:</span> <span class="text-gray-900">{{ $student->classSection?->school?->name ?? '—' }}</span></div>
                                    <div><span class="text-gray-500">{{ __('Class') }}:</span> <span class="text-gray-900">{{ $student->classSection?->full_name ?? '—' }}</span></div>
                                    <div><span class="text-gray-500">{{ __('Lead status') }}:</span>
                                        @php
                                            $labels = [
                                                'lead' => __('Lead'),
                                                'interested' => __('Interested'),
                                                'not_interested' => __('Not Interested'),
                                                'walkin_done' => __('Walk-in Done'),
                                                'admission_done' => __('Admission Done'),
                                                'follow_up_later' => __('Follow-up Later'),
                                            ];
                                            $currentLead = $student->lead_status ?? 'lead';
                                        @endphp
                                        <span class="text-gray-900">{{ $labels[$currentLead] ?? ucfirst(str_replace('_', ' ', $currentLead)) }}</span>
                                    </div>
                                    <div><span class="text-gray-500">{{ __('Phone') }}:</span>
                                        <span class="text-gray-900">
                                            @php $phones = array_filter([$student->whatsapp_phone_primary, $student->whatsapp_phone_secondary]); @endphp
                                            @forelse ($phones as $p)
                                                <a href="{{ route('students.show', $student).'#messages' }}" class="text-indigo-600 hover:text-indigo-800">
                                                    {{ \App\Models\Student::formatPhoneForDisplay($p) }}
                                                </a>@if (!$loop->last)<span class="text-gray-400"> · </span>@endif
                                            @empty
                                                —
                                            @endforelse
                                        </span>
                                    </div>
                                </dl>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop: table with School column --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Name') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Father') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('School') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Class') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Lead status') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Phone') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($students as $student)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('students.show', $student) }}" class="font-medium text-gray-900 hover:text-indigo-700">
                                                {{ $student->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $student->father_name ?: '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $student->classSection?->school?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $student->classSection?->full_name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            @php
                                                $labels = [
                                                    'lead' => __('Lead'),
                                                    'interested' => __('Interested'),
                                                    'not_interested' => __('Not Interested'),
                                                    'walkin_done' => __('Walk-in Done'),
                                                    'admission_done' => __('Admission Done'),
                                                    'follow_up_later' => __('Follow-up Later'),
                                                ];
                                                $currentLead = $student->lead_status ?? 'lead';
                                            @endphp
                                            {{ $labels[$currentLead] ?? ucfirst(str_replace('_', ' ', $currentLead)) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            @php $phones = array_filter([$student->whatsapp_phone_primary, $student->whatsapp_phone_secondary]); @endphp
                                            @forelse ($phones as $p)
                                                <a href="{{ route('students.show', $student).'#messages' }}" class="text-indigo-600 hover:text-indigo-800">
                                                    {{ \App\Models\Student::formatPhoneForDisplay($p) }}
                                                </a>@if (!$loop->last)<span class="text-gray-400"> · </span>@endif
                                            @empty
                                                —
                                            @endforelse
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('students.edit', $student) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">{{ __('Edit') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200">{{ $students->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
