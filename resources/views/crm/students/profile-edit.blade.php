<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit student') }}</h2>
                <div class="mt-1 text-xs text-gray-500">{{ $student->name }}</div>
            </div>
            <a href="{{ route('students.show', $student) }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                {{ __('Back to profile') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800">
                    <div class="font-semibold">{{ __('Please fix the errors below.') }}</div>
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-sm p-5">
                <form method="POST" action="{{ route('students.profile.update', $student) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs font-medium text-gray-600">{{ __('Name') }}</label>
                        <input name="name" value="{{ old('name', $student->name) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                        @error('name') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">{{ __('WhatsApp phone (primary)') }}</label>
                            <input name="whatsapp_phone_primary" value="{{ old('whatsapp_phone_primary', $student->whatsapp_phone_primary) }}"
                                   placeholder="9876543210"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            @error('whatsapp_phone_primary') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">{{ __('WhatsApp phone (secondary)') }}</label>
                            <input name="whatsapp_phone_secondary" value="{{ old('whatsapp_phone_secondary', $student->whatsapp_phone_secondary) }}"
                                   placeholder="9876543210"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            @error('whatsapp_phone_secondary') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">{{ __('School / class') }}</label>
                            <select name="class_section_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                                @foreach ($classSections as $cs)
                                    <option value="{{ $cs->id }}" {{ (string) old('class_section_id', $student->class_section_id) === (string) $cs->id ? 'selected' : '' }}>
                                        {{ $cs->school?->name ?? '—' }} — {{ $cs->full_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_section_id') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">{{ __('Lead status') }}</label>
                            @php
                                $leadStatuses = [
                                    'lead' => __('Lead'),
                                    'interested' => __('Interested'),
                                    'not_interested' => __('Not Interested'),
                                    'walkin_done' => __('Walk-in Done'),
                                    'admission_done' => __('Admission Done'),
                                    'follow_up_later' => __('Follow-up Later'),
                                ];
                                $current = old('lead_status', $student->lead_status ?? 'lead');
                            @endphp
                            <select name="lead_status" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                                @foreach ($leadStatuses as $value => $label)
                                    <option value="{{ $value }}" {{ $current === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('lead_status') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <details class="rounded-md border border-gray-200 p-3">
                        <summary class="text-sm font-medium text-gray-700 cursor-pointer">{{ __('More (optional)') }}</summary>
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600">{{ __('Father name') }}</label>
                                <input name="father_name" value="{{ old('father_name', $student->father_name) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                @error('father_name') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">{{ __('Roll number') }}</label>
                                <input name="roll_number" value="{{ old('roll_number', $student->roll_number) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                @error('roll_number') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">{{ __('Admission number') }}</label>
                                <input name="admission_number" value="{{ old('admission_number', $student->admission_number) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                @error('admission_number') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </details>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex justify-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                            {{ __('Save changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

