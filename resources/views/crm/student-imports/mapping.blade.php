<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('student-imports.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Map columns') }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-slate-600 mb-4">
                    {{ __('Map each Excel column to a student field. Rows: :count', ['count' => $import->total_rows]) }}
                </p>
                <form method="POST" action="{{ route('student-imports.save-mapping', $import) }}" class="space-y-4">
                    @csrf
                    <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <p class="text-sm font-medium text-slate-800 mb-2">{{ __('When a phone number already exists in the database') }}</p>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="duplicate_phone_policy" value="skip" {{ old('duplicate_phone_policy', 'skip') === 'skip' ? 'checked' : '' }} class="rounded-full border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-slate-700">{{ __('Skip the row') }}</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="duplicate_phone_policy" value="overwrite" {{ old('duplicate_phone_policy') === 'overwrite' ? 'checked' : '' }} class="rounded-full border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-slate-700">{{ __('Overwrite with new details (map latest data to that record)') }}</span>
                            </label>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">{{ __('Only Indian 10-digit numbers are accepted; +91 is added automatically.') }}</p>
                    </div>
                    <div class="space-y-3">
                        @foreach ($headers as $index => $header)
                            <div class="flex flex-wrap items-center gap-2 py-2 border-b border-gray-100">
                                <span class="font-mono text-sm text-gray-500 w-8">#{{ $index + 1 }}</span>
                                <span class="font-medium text-gray-700 min-w-[120px]">{{ $header ?: __('(empty)') }}</span>
                                <input type="hidden" name="column_names[{{ $index }}]" value="{{ $header }}">
                                <select name="mappings[{{ $index }}]" class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach ($targetFields as $value => $label)
                                        <option value="{{ $value }}" {{ (old('mappings.'.$index) ?? $import->columnMappings->firstWhere('column_index', $index)?->target_field) === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex gap-3 pt-4">
                        <x-primary-button>{{ __('Save & process import') }}</x-primary-button>
                        <a href="{{ route('student-imports.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
