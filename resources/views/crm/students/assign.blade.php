<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Assign leads to telecaller') }}</h2>
            <a href="{{ route('students.index') }}"
               class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                {{ __('Back to students') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6 space-y-4">
                <form method="GET" action="{{ route('students.assign') }}" class="space-y-3 border-b border-gray-200 pb-4">
                    <div class="flex flex-wrap gap-3 items-end">
                        <div class="min-w-[140px]">
                            <label class="block text-xs font-medium text-gray-500">{{ __('School') }}</label>
                            <select name="school_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="">{{ __('All') }}</option>
                                @foreach ($schools as $s)
                                    <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-[140px]">
                            <label class="block text-xs font-medium text-gray-500">{{ __('Session') }}</label>
                            <select name="session_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="">{{ __('All') }}</option>
                                @foreach ($sessions as $s)
                                    <option value="{{ $s->id }}" {{ request('session_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-[160px]">
                            <label class="block text-xs font-medium text-gray-500">{{ __('Class') }}</label>
                            <select name="class_name" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="">{{ __('All') }}</option>
                                @foreach (($classOptions ?? []) as $cls)
                                    <option value="{{ $cls }}" {{ (string) request('class_name') === (string) $cls ? 'selected' : '' }}>
                                        {{ $cls }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="only_unassigned" name="only_unassigned" value="1"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                   {{ request('only_unassigned') === '1' ? 'checked' : '' }}>
                            <label for="only_unassigned" class="text-xs text-gray-700">{{ __('Only unassigned') }}</label>
                        </div>
                        <button type="submit"
                                class="px-4 py-2 bg-gray-800 text-white text-xs rounded-md hover:bg-gray-700">
                            {{ __('Filter') }}
                        </button>
                    </div>
                    <p class="text-xs text-gray-500">
                        {{ __('Use the filters to narrow down students (for example by school and class) and optionally show only unassigned leads.') }}
                    </p>
                </form>

                @if ($students->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No students match the selected filters.') }}</p>
                @else
                    <form method="POST" action="{{ route('students.assign.perform') }}" class="space-y-3">
                        @csrf
                        <div class="flex flex-wrap gap-3 items-end">
                            <div class="min-w-[200px]">
                                <label class="block text-xs font-medium text-gray-500">{{ __('Assign selected to') }}</label>
                                <select name="assigned_to" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                                    <option value="">{{ __('Select telecaller') }}</option>
                                    @foreach ($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit"
                                    class="px-4 py-2 bg-emerald-600 text-white text-xs rounded-md hover:bg-emerald-700">
                                {{ __('Assign leads') }}
                            </button>
                            <p class="text-xs text-gray-500 ml-auto">
                                {{ __('Page shows :count students. Selection is per page.', ['count' => $students->count()]) }}
                            </p>
                        </div>

                        <div class="overflow-x-auto border border-gray-200 rounded-md">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2">
                                            <input type="checkbox" id="select_all_students"
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Name') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('School / Class') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Phone') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Current assignee') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($students as $student)
                                        @php
                                            $phones = array_filter([$student->whatsapp_phone_primary, $student->whatsapp_phone_secondary]);
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2">
                                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                                       class="student-select rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="font-medium text-gray-900">{{ $student->name }}</div>
                                                @if ($student->father_name)
                                                    <div class="text-xs text-gray-500">{{ __('S/o') }} {{ $student->father_name }}</div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-xs text-gray-600">
                                                {{ $student->classSection?->school?->name ?? '—' }}<br>
                                                {{ $student->classSection?->full_name ?? '—' }}
                                            </td>
                                            <td class="px-3 py-2 text-xs text-gray-600">
                                                @if (!empty($phones))
                                                    @foreach ($phones as $p)
                                                        {{ \App\Models\Student::formatPhoneForDisplay($p) }}@if(!$loop->last)<span class="text-gray-400"> · </span>@endif
                                                    @endforeach
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-xs text-gray-600">
                                                {{ $student->assignedTo?->name ?? __('Unassigned') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div>{{ $students->links() }}</div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <script>
        const selectAll = document.getElementById('select_all_students');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.student-select').forEach(function (cb) {
                    cb.checked = selectAll.checked;
                });
            });
        }

    </script>
</x-app-layout>

