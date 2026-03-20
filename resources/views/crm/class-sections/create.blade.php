<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('class-sections.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add Class / Section') }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('class-sections.store') }}" class="space-y-4">
                    @csrf
                    @if (request('return_to'))
                        <input type="hidden" name="return_to" value="{{ request('return_to') }}">
                    @endif
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <x-input-label for="school_id" :value="__('School')" />
                            <a href="{{ route('schools.create') }}" class="text-xs text-indigo-600 hover:text-indigo-800">{{ __('Add school') }}</a>
                        </div>
                        <select id="school_id" name="school_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('Select school') }}</option>
                            @foreach ($schools as $s)
                                <option value="{{ $s->id }}" {{ old('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('school_id')" class="mt-1" />
                    </div>
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <x-input-label for="academic_session_id" :value="__('Academic session')" />
                            <a href="{{ route('sessions.create') }}" class="text-xs text-indigo-600 hover:text-indigo-800">{{ __('Add session') }}</a>
                        </div>
                        <select id="academic_session_id" name="academic_session_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('Select session') }}</option>
                            @foreach ($sessions as $s)
                                <option value="{{ $s->id }}" {{ old('academic_session_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('academic_session_id')" class="mt-1" />
                    </div>

                    <div class="pt-1">
                        <button type="button" id="neetJeePresetBtn"
                                class="w-full inline-flex items-center justify-center px-3 py-2 bg-emerald-600 text-white text-sm font-medium rounded-md hover:bg-emerald-700 transition">
                            {{ __('Add NEET/JEE presets (9-13)') }}
                        </button>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ __('Creates only missing class/stream combinations for this school + session. No merging with existing ones.') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="class_name" :value="__('Class (e.g. 6, 7)')" />
                            <x-text-input id="class_name" name="class_name" class="block mt-1 w-full" :value="old('class_name')" required />
                            <x-input-error :messages="$errors->get('class_name')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="section_name" :value="__('Section (e.g. A, B)')" />
                            <x-text-input id="section_name" name="section_name" class="block mt-1 w-full" :value="old('section_name')" />
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <x-primary-button>{{ __('Create') }}</x-primary-button>
                        <a href="{{ route('class-sections.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>

            <form method="POST" action="{{ route('class-sections.presets.neet-jee') }}" id="neetJeePresetForm">
                @csrf
                <input type="hidden" name="school_id" id="neetJeePresetSchoolId">
                <input type="hidden" name="academic_session_id" id="neetJeePresetSessionId">
            </form>
        </div>
    </div>

    <script>
        (function () {
            const btn = document.getElementById('neetJeePresetBtn');
            if (!btn) return;
            const schoolSelect = document.getElementById('school_id');
            const sessionSelect = document.getElementById('academic_session_id');
            const presetSchool = document.getElementById('neetJeePresetSchoolId');
            const presetSession = document.getElementById('neetJeePresetSessionId');
            const presetForm = document.getElementById('neetJeePresetForm');

            btn.addEventListener('click', function () {
                if (presetSchool && schoolSelect) presetSchool.value = schoolSelect.value;
                if (presetSession && sessionSelect) presetSession.value = sessionSelect.value;
                presetForm?.submit();
            });
        })();
    </script>
</x-app-layout>
