<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Redistribute leads') }}</h2>
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
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-disc ml-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6 space-y-4">
                <form method="GET" action="{{ route('students.assign') }}" class="space-y-3 border-b border-gray-200 pb-4">
                    <div class="flex flex-wrap gap-3 items-end">
                        <div class="min-w-[140px]">
                            <label class="block text-xs font-medium text-gray-500">{{ __('School') }}</label>
                            <select name="school_id" data-school-search="1" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
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
                        <div class="min-w-[180px]">
                            <label class="block text-xs font-medium text-gray-500">{{ __('Currently assigned to') }}</label>
                            <select name="current_assigned_to" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="">{{ __('All') }}</option>
                                <option value="unassigned" {{ request('current_assigned_to') === 'unassigned' ? 'selected' : '' }}>{{ __('Unassigned') }}</option>
                                @foreach (($telecallers ?? collect()) as $u)
                                    <option value="{{ $u->id }}" {{ (string) request('current_assigned_to') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
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
                    @php
                        $currentOwnerFilter = (string) request('current_assigned_to', '');
                    @endphp
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs font-semibold text-slate-700">{{ __('Lead selection') }}</p>
                            <p class="text-xs text-slate-500">{{ __('Page shows :count students. Selection is per page.', ['count' => $students->count()]) }}</p>
                        </div>
                        <div class="mt-3 overflow-x-auto border border-gray-200 rounded-md">
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
                                                <input type="checkbox" data-student-id="{{ $student->id }}"
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
                        <div class="mt-3">{{ $students->links() }}</div>
                    </div>

                    <form method="POST" action="{{ route('students.transfer.perform') }}" class="rounded-lg border border-indigo-200 bg-indigo-50 p-3 space-y-3 js-lead-action-form">
                        @csrf
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-indigo-800">{{ __('Redistribute leads') }}</p>
                            <span class="text-[11px] text-indigo-700">{{ __('Secure flow with audit log') }}</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-500">{{ __('Redistribute to') }}</label>
                                <select name="transfer_to" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                                    <option value="">{{ __('Select telecaller') }}</option>
                                    @foreach (($telecallers ?? $users) as $u)
                                        @if ($currentOwnerFilter !== '' && $currentOwnerFilter !== 'unassigned' && $currentOwnerFilter === (string) $u->id)
                                            @continue
                                        @endif
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">{{ __('Reason (optional)') }}</label>
                                <input type="text" name="transfer_reason" maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 text-sm" placeholder="{{ __('Workload balancing') }}">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">{{ __('Type to confirm') }}</label>
                                <input type="text" name="confirm_phrase" class="mt-1 block w-full rounded-md border-gray-300 text-sm" placeholder="TRANSFER" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">{{ __('Admin password') }}</label>
                                <input type="password" name="admin_password" class="mt-1 block w-full rounded-md border-gray-300 text-sm" autocomplete="current-password" required>
                            </div>
                        </div>
                        <div class="js-selected-ids"></div>
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-700 text-white text-xs rounded-md hover:bg-indigo-800">
                            {{ __('Redistribute selected leads') }}
                        </button>
                        <p class="text-[11px] text-indigo-800">
                            {{ __('Lead status, follow-up date, call notes and call history stay unchanged. Only owner changes with timeline log.') }}
                        </p>
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
        const actionForms = document.querySelectorAll('.js-lead-action-form');
        const selectedIds = function () {
            return Array.from(document.querySelectorAll('.student-select:checked'))
                .map(function (el) { return el.getAttribute('data-student-id'); })
                .filter(Boolean);
        };
        actionForms.forEach(function (formEl) {
            formEl.addEventListener('submit', function (e) {
                const ids = selectedIds();
                if (!ids.length) {
                    e.preventDefault();
                    alert('Select at least one lead from the table first.');
                    return;
                }
                const holder = formEl.querySelector('.js-selected-ids');
                if (!holder) return;
                holder.innerHTML = '';
                ids.forEach(function (id) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'student_ids[]';
                    input.value = id;
                    holder.appendChild(input);
                });
            });
        });
    </script>
</x-app-layout>

