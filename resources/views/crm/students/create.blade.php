<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('students.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add Student') }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('students.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <x-input-label for="class_section_id" :value="__('Class / Section')" />
                            <a href="{{ route('class-sections.create', ['return_to' => 'students/create']) }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('Add new class') }}</a>
                        </div>
                        <select id="class_section_id" name="class_section_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('Select class') }}</option>
                            @foreach ($classSections as $cs)
                                <option value="{{ $cs->id }}" {{ old('class_section_id', request('class_section_id')) == $cs->id ? 'selected' : '' }}>
                                    {{ $cs->full_name }} — {{ $cs->school->name }} ({{ $cs->academicSession->name ?? '—' }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('class_section_id')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="name" :value="__('Student name')" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="father_name" :value="__('Father name')" />
                        <x-text-input id="father_name" name="father_name" class="block mt-1 w-full" :value="old('father_name')" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="whatsapp_phone_primary" :value="__('WhatsApp (primary)')" />
                            <x-phone-input name="whatsapp_phone_primary" :value="old('whatsapp_phone_primary')" class="mt-1 block w-full" />
                            <p class="text-xs text-slate-500 mt-1">{{ __('10 digits only. +91 is fixed.') }}</p>
                            <x-input-error :messages="$errors->get('whatsapp_phone_primary')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="whatsapp_phone_secondary" :value="__('WhatsApp (secondary)')" />
                            <x-phone-input name="whatsapp_phone_secondary" :value="old('whatsapp_phone_secondary')" class="mt-1 block w-full" />
                            <p class="text-xs text-slate-500 mt-1">{{ __('10 digits only. Must be unique.') }}</p>
                            <x-input-error :messages="$errors->get('whatsapp_phone_secondary')" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="lead_status" :value="__('Lead status')" />
                        <select id="lead_status" name="lead_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="lead" {{ old('lead_status', 'lead') === 'lead' ? 'selected' : '' }}>{{ __('Lead') }}</option>
                            <option value="interested" {{ old('lead_status') === 'interested' ? 'selected' : '' }}>{{ __('Interested') }}</option>
                            <option value="not_interested" {{ old('lead_status') === 'not_interested' ? 'selected' : '' }}>{{ __('Not Interested') }}</option>
                            <option value="walkin_done" {{ old('lead_status') === 'walkin_done' ? 'selected' : '' }}>{{ __('Walk-in Done') }}</option>
                            <option value="admission_done" {{ old('lead_status') === 'admission_done' ? 'selected' : '' }}>{{ __('Admission Done') }}</option>
                            <option value="follow_up_later" {{ old('lead_status') === 'follow_up_later' ? 'selected' : '' }}>{{ __('Follow-up Later') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('lead_status')" class="mt-1" />
                    </div>
                    <div class="flex gap-3 pt-2">
                        <x-primary-button>{{ __('Add Student') }}</x-primary-button>
                        <a href="{{ route('students.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
