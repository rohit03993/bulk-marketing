<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ __('Danger Zone') }}</h2>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-500 hover:text-blue-600 transition">← {{ __('Back to Admin') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl border border-red-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-red-50 border-b border-red-100">
                    <p class="font-medium text-red-800">{{ __('Permanent Delete Console') }}</p>
                    <p class="mt-1 text-sm text-red-700">{{ __('This action is irreversible. It can delete students, classes, schools and their related call/campaign history.') }}</p>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <p class="text-sm text-slate-600">{{ __('Current totals in system:') }}</p>
                    <ul class="text-sm text-slate-700 space-y-1 list-disc list-inside">
                        <li>{{ __('Schools') }} ({{ number_format($counts['schools']) }})</li>
                        <li>{{ __('Academic sessions') }} ({{ number_format($counts['sessions']) }})</li>
                        <li>{{ __('Classes / sections') }} ({{ number_format($counts['class_sections']) }})</li>
                        <li>{{ __('Students') }} ({{ number_format($counts['students']) }})</li>
                        <li>{{ __('Templates') }} ({{ number_format($counts['templates']) }})</li>
                        <li>{{ __('Campaigns') }} ({{ number_format($counts['campaigns']) }})</li>
                        <li>{{ __('Student imports') }} ({{ number_format($counts['imports']) }})</li>
                        <li>{{ __('Lead / call history') }} ({{ number_format($counts['student_calls']) }})</li>
                        <li>{{ __('Assignment transfers') }} ({{ number_format($counts['assignment_transfers'] ?? 0) }})</li>
                        <li>{{ __('Tags & lists') }} ({{ number_format($counts['tags']) }})</li>
                        <li>{{ __('Staff user accounts') }} ({{ number_format($counts['staff_users']) }})</li>
                    </ul>

                    @php
                        $scopeValue = old('scope', 'students');
                        $selectedSchoolId = (int) collect(old('school_ids', []))->first();
                    @endphp
                    <form method="POST" action="{{ route('admin.reset-data.perform') }}" class="space-y-5 pt-2" id="resetDataForm">
                        @csrf
                        <div>
                            <label for="scope" class="block text-sm font-medium text-slate-700">{{ __('Delete scope') }}</label>
                            <select id="scope" name="scope" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                <option value="all" {{ $scopeValue === 'all' ? 'selected' : '' }}>{{ __('Everything — schools, sessions, classes, students, campaigns, imports, staff logins') }}</option>
                                <option value="school" {{ $scopeValue === 'school' ? 'selected' : '' }}>{{ __('Delete full school — classes, students, imports & history inside') }}</option>
                                <option value="class_section" {{ $scopeValue === 'class_section' ? 'selected' : '' }}>{{ __('Selected class/section(s) only + students/history inside') }}</option>
                                <option value="students" {{ $scopeValue === 'students' ? 'selected' : '' }}>{{ __('Selected students only (by ID) + their history') }}</option>
                            </select>
                            @error('scope')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="schoolBlock" class="{{ $scopeValue === 'school' ? '' : 'hidden' }} space-y-3">
                            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                                {{ __('A school can only be deleted when no student is both assigned to staff and already called. Uncalled assigned leads and unassigned students in that school will be removed.') }}
                            </p>
                            <div>
                                <label for="school_id" class="block text-sm font-medium text-slate-700">{{ __('Select school to delete') }}</label>
                                <select id="school_id" name="school_ids[]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                    <option value="">{{ __('— Choose a school —') }}</option>
                                    @foreach ($schools as $school)
                                        @php
                                            $preview = collect($schoolDeletionPreview ?? [])->firstWhere('id', $school->id);
                                        @endphp
                                        <option value="{{ $school->id }}" @selected($selectedSchoolId === $school->id) @disabled($preview && ! $preview['can_delete'])>
                                            {{ $school->name }}
                                            @if ($preview)
                                                ({{ $preview['blocking'] > 0 ? __(':blocking assigned+called — blocked', ['blocking' => $preview['blocking']]) : __('OK to delete') }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-slate-500">{{ __('Schools marked “blocked” cannot be deleted until those leads are handled.') }}</p>
                            </div>
                            <div class="overflow-x-auto rounded-md border border-slate-200">
                                <table class="min-w-full text-xs text-slate-700">
                                    <thead class="bg-slate-50 text-left">
                                        <tr>
                                            <th class="px-3 py-2 font-medium">{{ __('School') }}</th>
                                            <th class="px-3 py-2 font-medium">{{ __('Students') }}</th>
                                            <th class="px-3 py-2 font-medium">{{ __('Assigned + called (blocks delete)') }}</th>
                                            <th class="px-3 py-2 font-medium">{{ __('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($schoolDeletionPreview ?? [] as $row)
                                            <tr>
                                                <td class="px-3 py-2">{{ $row['name'] }}</td>
                                                <td class="px-3 py-2">{{ number_format($row['total_students']) }}</td>
                                                <td class="px-3 py-2">{{ number_format($row['blocking']) }}</td>
                                                <td class="px-3 py-2">
                                                    @if ($row['can_delete'])
                                                        <span class="text-green-700 font-medium">{{ __('Can delete') }}</span>
                                                    @else
                                                        <span class="text-red-700 font-medium">{{ __('Blocked') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @error('school_ids')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="classBlock" class="{{ $scopeValue === 'class_section' ? '' : 'hidden' }}">
                            <label for="class_section_ids" class="block text-sm font-medium text-slate-700">{{ __('Select class/section(s)') }}</label>
                            <select id="class_section_ids" name="class_section_ids[]" multiple size="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                @foreach ($classSections as $classSection)
                                    <option value="{{ $classSection->id }}" @selected(collect(old('class_section_ids', []))->contains($classSection->id))>
                                        {{ $classSection->class_name }}{{ $classSection->section_name ? ' - '.$classSection->section_name : '' }} ({{ $classSection->school?->name ?? '—' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('class_section_ids')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="studentsBlock" class="{{ $scopeValue === 'students' ? '' : 'hidden' }}">
                            <label for="student_ids" class="block text-sm font-medium text-slate-700">{{ __('Student IDs (comma or space separated)') }}</label>
                            <textarea id="student_ids" name="student_ids" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" placeholder="101, 102, 203">{{ old('student_ids') }}</textarea>
                            @error('student_ids')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700">{{ __('Admin password') }}</label>
                            <input type="password" id="password" name="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" autocomplete="current-password">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="confirm" value="1" required
                                   class="rounded border-gray-300 text-red-600 focus:ring-red-500 mt-0.5">
                            <span class="text-sm text-slate-700">{{ __('I understand this will permanently delete all of the above. This cannot be undone.') }}</span>
                        </label>
                        @error('confirm')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div>
                            <label for="confirm_phrase" class="block text-sm font-medium text-slate-700">{{ __('Type :phrase to confirm', ['phrase' => 'DELETE DATA NOW']) }}</label>
                            <input type="text" id="confirm_phrase" name="confirm_phrase" value="{{ old('confirm_phrase') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"
                                   placeholder="DELETE DATA NOW" autocomplete="off">
                            @error('confirm_phrase')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="submit" id="submitDelete" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                {{ __('Run deletion') }}
                            </button>
                            <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const scopeEl = document.getElementById('scope');
            const studentsBlock = document.getElementById('studentsBlock');
            const classBlock = document.getElementById('classBlock');
            const schoolBlock = document.getElementById('schoolBlock');
            const submitBtn = document.getElementById('submitDelete');
            const schoolSelect = document.getElementById('school_id');
            const studentIds = document.getElementById('student_ids');
            const classSelect = document.getElementById('class_section_ids');

            function setBlockVisible(block, visible) {
                if (!block) return;
                block.classList.toggle('hidden', !visible);
                block.querySelectorAll('input, select, textarea').forEach(function (el) {
                    el.disabled = !visible;
                });
            }

            function syncScopeBlocks() {
                const scope = scopeEl ? scopeEl.value : 'students';
                setBlockVisible(studentsBlock, scope === 'students');
                setBlockVisible(classBlock, scope === 'class_section');
                setBlockVisible(schoolBlock, scope === 'school');
                if (submitBtn) {
                    submitBtn.textContent = scope === 'all'
                        ? @json(__('Delete everything listed above'))
                        : @json(__('Run deletion'));
                }
            }

            if (scopeEl) {
                scopeEl.addEventListener('change', syncScopeBlocks);
                syncScopeBlocks();
            }
        });
    </script>
</x-app-layout>
