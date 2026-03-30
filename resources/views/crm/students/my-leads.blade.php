<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Leads') }} — {{ auth()->user()->name }}
            </h2>
            <a href="{{ route('students.index') }}"
               class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                {{ __('All students') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('Add Lead') }}</h3>
                        <p class="text-xs text-gray-500 mt-1">{{ __('New leads will be created as Uncalled and assigned to you.') }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('students.my-leads.add') }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf

                    <div>
                        <label class="block text-xs font-medium text-gray-500">{{ __('Class (fixed presets)') }}</label>
                        <select name="class_preset_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm @error('class_preset_id') border-red-500 @enderror">
                            <option value="">{{ __('Select class') }}</option>
                            @foreach ($classPresets as $p)
                                <option value="{{ $p->id }}"
                                        {{ (string) old('class_preset_id', request('class_preset_id')) === (string) $p->id ? 'selected' : '' }}>
                                    {{ $p->display_label }}
                                </option>
                            @endforeach
                        </select>
                        @error('class_preset_id')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500">{{ __('School') }}</label>
                        <select name="school_id"
                                id="schoolChoice"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm @error('school_id') border-red-500 @enderror">
                            @foreach ($schools as $s)
                                <option value="{{ $s->id }}" {{ (string) old('school_id') === (string) $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                            <option value="not_in_list" {{ old('school_id') === 'not_in_list' ? 'selected' : '' }}>
                                {{ __('Not in list') }}
                            </option>
                        </select>
                        @error('school_id')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="newSchoolWrap" style="{{ old('school_id') === 'not_in_list' ? '' : 'display:none' }}">
                        <label class="block text-xs font-medium text-gray-500">{{ __('Enter school name') }}</label>
                        <input type="text"
                               name="new_school_name"
                               value="{{ old('new_school_name') }}"
                               placeholder="{{ __('Type school name') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm @error('new_school_name') border-red-500 @enderror">
                        @error('new_school_name')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500">{{ __('Student name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('Enter student name') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm @error('name') border-red-500 @enderror">
                        @error('name')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500">{{ __('Father name') }}</label>
                        <input type="text" name="father_name" value="{{ old('father_name') }}" placeholder="{{ __('Enter father name') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm @error('father_name') border-red-500 @enderror">
                        @error('father_name')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500">{{ __('WhatsApp (primary)') }}</label>
                        <x-phone-input name="whatsapp_phone_primary" :value="old('whatsapp_phone_primary')" class="mt-1 block w-full @error('whatsapp_phone_primary') border-red-500 @enderror" />
                        @error('whatsapp_phone_primary')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500">{{ __('WhatsApp (secondary)') }}</label>
                        <x-phone-input name="whatsapp_phone_secondary" :value="old('whatsapp_phone_secondary')" class="mt-1 block w-full @error('whatsapp_phone_secondary') border-red-500 @enderror" />
                        @error('whatsapp_phone_secondary')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-span-2 flex gap-3 pt-1">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700 disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                            {{ __('Add Lead') }}
                        </button>
                        <a href="{{ route('students.my-leads') }}"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>
            </div>

            <form method="GET" action="{{ route('students.my-leads') }}" class="mb-4 space-y-2">
                <div class="flex flex-wrap gap-2 items-end">
                    <div class="min-w-[240px]">
                        <label class="block text-xs font-medium text-gray-500">{{ __('School') }}</label>
                        <select name="school_id" data-school-search="1" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">{{ __('All schools') }}</option>
                            @foreach ($schools as $s)
                                <option value="{{ $s->id }}" {{ (string) request('school_id') === (string) $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[200px]">
                        <label class="flex items-center gap-2 text-xs font-medium text-gray-500">
                            <input
                                type="checkbox"
                                name="added_by_me"
                                value="1"
                                {{ request('added_by_me') ? 'checked' : '' }}
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            {{ __('Added by me') }}
                        </label>
                    </div>
                    <div class="min-w-[140px]">
                        <label class="block text-xs font-medium text-gray-500">{{ __('Status') }}</label>
                        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="all">{{ __('All') }}</option>
                            @php
                                $leadStatuses = [
                                    'lead' => __('Uncalled'),
                                    'interested' => __('Interested'),
                                    'not_interested' => __('Not Interested'),
                                    'converted' => __('Converted (Walk-in + Admission)'),
                                    'walkin_done' => __('Walk-in Done'),
                                    'admission_done' => __('Admission Done'),
                                    'follow_up_later' => __('Follow-up Later'),
                                ];
                            @endphp
                            @foreach ($leadStatuses as $value => $label)
                                <option value="{{ $value }}" {{ request('status', 'all') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[120px]">
                        <label class="block text-xs font-medium text-gray-500">{{ __('Called') }}</label>
                        <select name="called" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">{{ __('Any') }}</option>
                            <option value="0" {{ request('called') === '0' ? 'selected' : '' }}>{{ __('Not called') }}</option>
                            <option value="1" {{ request('called') === '1' ? 'selected' : '' }}>{{ __('Called') }}</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-medium text-gray-500">{{ __('Search name / phone') }}</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search…') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">{{ __('Filter') }}</button>
                </div>
            </form>

            <script>
                (function () {
                    const choice = document.getElementById('schoolChoice');
                    const wrap = document.getElementById('newSchoolWrap');
                    if (!choice || !wrap) return;
                    const sync = function () {
                        wrap.style.display = choice.value === 'not_in_list' ? '' : 'none';
                    };
                    choice.addEventListener('change', sync);
                    sync();
                })();
            </script>

            @if ($students->isEmpty())
                <div class="bg-white rounded-lg shadow-sm p-6 text-center text-gray-500">
                    {{ __('No leads assigned to you yet.') }}
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($students as $student)
                        @php
                            $phones = array_filter([$student->whatsapp_phone_primary, $student->whatsapp_phone_secondary]);
                            $lead = $student->lead_status ?? 'lead';
                            $label = $leadStatuses[$lead] ?? ucfirst(str_replace('_',' ',$lead));
                            $badgeClasses = match ($lead) {
                                'lead' => 'bg-sky-100 text-sky-800',
                                'interested' => 'bg-emerald-100 text-emerald-800',
                                'follow_up_later' => 'bg-amber-100 text-amber-800',
                                'walkin_done' => 'bg-indigo-100 text-indigo-800',
                                'admission_done' => 'bg-green-100 text-green-800',
                                'not_interested' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800',
                            };
                        @endphp
                        <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col justify-between">
                            <div class="flex justify-between items-start gap-2">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">
                                        <a href="{{ route('students.show', $student) }}" class="hover:text-indigo-700">
                                            {{ $student->name }}
                                        </a>
                                        @if ($student->total_calls == 0)
                                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-800">
                                                {{ __('NOT CALLED') }}
                                            </span>
                                        @endif
                                    </h3>
                                    @if ($student->father_name)
                                        <p class="text-xs text-gray-500">{{ __('S/o') }} {{ $student->father_name }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $student->classSection?->school?->name ?? '—' }}
                                        @if ($student->classSection)
                                            · {{ $student->classSection->full_name }}
                                        @endif
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $badgeClasses }}">
                                    {{ $label }}
                                </span>
                            </div>

                            <div class="mt-3 text-xs text-gray-600 space-y-1">
                                <div>
                                    <span class="font-medium">{{ __('Phone:') }}</span>
                                    @if (!empty($phones))
                                        @foreach ($phones as $p)
                                            <a href="{{ route('students.show', $student) }}#messages" class="text-indigo-600 hover:text-indigo-800">
                                                {{ \App\Models\Student::formatPhoneForDisplay($p) }}
                                            </a>@if (!$loop->last)<span class="text-gray-400"> · </span>@endif
                                        @endforeach
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </div>
                                <div>
                                    <span class="font-medium">{{ __('Calls:') }}</span>
                                    @if ($student->total_calls > 0)
                                        {{ $student->total_calls }}
                                        @if ($student->last_call_at)
                                            <span class="text-gray-500">({{ $student->last_call_at->diffForHumans() }})</span>
                                        @endif
                                    @else
                                        <span class="text-red-600 font-medium">{{ __('Never called') }}</span>
                                    @endif
                                </div>
                                @if ($student->next_followup_at)
                                    <div>
                                        <span class="font-medium">{{ __('Next follow-up:') }}</span>
                                        <span class="{{ $student->next_followup_at->isPast() ? 'text-red-600 font-semibold' : '' }}">
                                            {{ $student->next_followup_at->format('d M, h:i A') }}
                                        </span>
                                    </div>
                                @endif
                                @if ($student->last_call_notes)
                                    <div class="mt-1 text-gray-500 bg-gray-50 rounded p-2">
                                        {{ \Illuminate\Support\Str::limit($student->last_call_notes, 80) }}
                                    </div>
                                @endif
                            </div>

                                <div class="mt-3 flex justify-between items-center">
                                <div class="flex gap-2">
                                    @if (!empty($phones))
                                        <a href="{{ route('students.show', $student) }}#messages"
                                           class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-gray-300 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            {{ __('Open history') }}
                                        </a>
                                    @endif
                                </div>
                                <a href="{{ route('students.show', $student) }}"
                                   class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                    {{ __('Profile') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">{{ $students->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>

