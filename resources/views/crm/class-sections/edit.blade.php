<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('class-sections.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Class / Section') }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('class-sections.update', $classSection) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <x-input-label for="school_id" :value="__('School')" />
                            <a href="{{ route('schools.create') }}" class="text-xs text-indigo-600 hover:text-indigo-800">{{ __('Add school') }}</a>
                        </div>
                        <select id="school_id" name="school_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($schools as $s)
                                <option value="{{ $s->id }}" {{ old('school_id', $classSection->school_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
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
                            @foreach ($sessions as $s)
                                <option value="{{ $s->id }}" {{ old('academic_session_id', $classSection->academic_session_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('academic_session_id')" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="class_name" :value="__('Class')" />
                            <x-text-input id="class_name" name="class_name" class="block mt-1 w-full" :value="old('class_name', $classSection->class_name)" required />
                        </div>
                        <div>
                            <x-input-label for="section_name" :value="__('Section')" />
                            <x-text-input id="section_name" name="section_name" class="block mt-1 w-full" :value="old('section_name', $classSection->section_name)" />
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <x-primary-button>{{ __('Update') }}</x-primary-button>
                        <a href="{{ route('class-sections.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
