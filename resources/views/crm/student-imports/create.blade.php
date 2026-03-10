<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('student-imports.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Import Students from Excel') }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-4">
                    {{ __('Select school, session and class (optional). Then upload an Excel file (xlsx, xls or csv). The first row should contain column headers. If you choose a class here, all imported students will be assigned to that class and it will be created under the school if needed.') }}
                </p>
                <form method="POST" action="{{ route('student-imports.store') }}" enctype="multipart/form-data" class="space-y-4" id="import-form">
                    @csrf
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <x-input-label for="school_id" :value="__('School')" />
                            <a href="{{ route('schools.create') }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('Add school') }}</a>
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
                            <x-input-label for="academic_session_id" :value="__('Academic session (optional)')" />
                            <a href="{{ route('sessions.create') }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('Add session') }}</a>
                        </div>
                        <select id="academic_session_id" name="academic_session_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('Select session') }}</option>
                            @foreach ($sessions as $s)
                                <option value="{{ $s->id }}" {{ old('academic_session_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="import_class" :value="__('Class (optional)')" />
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('Assign all imported students to this class. The class will be created under the school if it does not exist.') }}</p>
                        <select id="import_class" name="import_class" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('— Use column mapping —') }}</option>
                            @foreach (range(1, 12) as $n)
                                <option value="{{ $n }}" {{ old('import_class') === (string) $n ? 'selected' : '' }}>{{ __('Class :n', ['n' => $n]) }}</option>
                            @endforeach
                            <option value="custom" {{ old('import_class') === 'custom' ? 'selected' : '' }}>{{ __('Custom class name') }}</option>
                        </select>
                        <div id="import_class_custom_wrap" class="mt-2 hidden">
                            <label for="import_class_custom" class="block text-sm font-medium text-gray-700">{{ __('Custom class name') }}</label>
                            <input type="text" id="import_class_custom" name="import_class_custom" value="{{ old('import_class_custom') }}" placeholder="e.g. KG, Nursery, 13"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('import_class_custom')" class="mt-1" />
                        </div>
                        <div class="mt-2">
                            <label for="import_section_name" class="block text-sm font-medium text-gray-700">{{ __('Section (optional)') }}</label>
                            <input type="text" id="import_section_name" name="import_section_name" value="{{ old('import_section_name') }}" placeholder="e.g. A, B"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <x-input-label for="tag_name" :value="__('Tag / list name (optional)')" />
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ __('All imported students will be tagged with this name, e.g. "DPS School", "Interested Candidates", "Walk-in Campaign".') }}
                        </p>
                        <input type="text" id="tag_name" name="tag_name" value="{{ old('tag_name') }}" placeholder="e.g. DPS School, Interested Candidates"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <x-input-error :messages="$errors->get('tag_name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="file" :value="__('Excel file')" />
                        <input type="file" id="file" name="file" accept=".xlsx,.xls,.csv" required
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <x-input-error :messages="$errors->get('file')" class="mt-1" />
                    </div>
                    <div class="flex gap-3 pt-2">
                        <x-primary-button>{{ __('Upload & map columns') }}</x-primary-button>
                        <a href="{{ route('student-imports.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('import_class').addEventListener('change', function () {
            document.getElementById('import_class_custom_wrap').classList.toggle('hidden', this.value !== 'custom');
        });
        if (document.getElementById('import_class').value === 'custom') {
            document.getElementById('import_class_custom_wrap').classList.remove('hidden');
        }
    </script>
</x-app-layout>
