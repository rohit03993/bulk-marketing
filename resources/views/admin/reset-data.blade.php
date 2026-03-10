<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Reset all data') }}
            </h2>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-500 hover:text-blue-600 transition">← {{ __('Back to Admin') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl border border-red-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-red-50 border-b border-red-100">
                    <p class="font-medium text-red-800">{{ __('This will permanently delete almost all CRM data.') }}</p>
                    <p class="mt-1 text-sm text-red-700">
                        {{ __('Only admin logins and system settings (e.g. Aisensy API) will be kept. All staff logins and operational data will be removed.') }}
                    </p>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <p class="text-sm text-slate-600">{{ __('The following will be deleted:') }}</p>
                    <ul class="text-sm text-slate-700 space-y-1 list-disc list-inside">
                        <li>{{ __('Schools') }} ({{ number_format($counts['schools']) }})</li>
                        <li>{{ __('Academic sessions') }} ({{ number_format($counts['sessions']) }})</li>
                        <li>{{ __('Classes / sections') }} ({{ number_format($counts['class_sections']) }})</li>
                        <li>{{ __('Students') }} ({{ number_format($counts['students']) }})</li>
                        <li>{{ __('Templates') }} ({{ number_format($counts['templates']) }})</li>
                        <li>{{ __('Campaigns') }} ({{ number_format($counts['campaigns']) }})</li>
                        <li>{{ __('Student imports') }} ({{ number_format($counts['imports']) }})</li>
                        <li>{{ __('Lead / call history') }} ({{ number_format($counts['student_calls']) }})</li>
                        <li>{{ __('Tags & lists') }} ({{ number_format($counts['tags']) }})</li>
                        <li>{{ __('Staff user accounts') }} ({{ number_format($counts['staff_users']) }})</li>
                    </ul>

                    <form method="POST" action="{{ route('admin.reset-data.perform') }}" class="space-y-4 pt-2">
                        @csrf
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="confirm" value="1" required
                                   class="rounded border-gray-300 text-red-600 focus:ring-red-500 mt-0.5">
                            <span class="text-sm text-slate-700">{{ __('I understand this will permanently delete all of the above. This cannot be undone.') }}</span>
                        </label>
                        @error('confirm')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div>
                            <label for="confirm_phrase" class="block text-sm font-medium text-slate-700">{{ __('Type :phrase to confirm', ['phrase' => 'RESET ALL DATA']) }}</label>
                            <input type="text" id="confirm_phrase" name="confirm_phrase" value="{{ old('confirm_phrase') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"
                                   placeholder="RESET ALL DATA" autocomplete="off">
                            @error('confirm_phrase')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                {{ __('Delete all data') }}
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
</x-app-layout>
