<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Add staff') }}
            </h2>
            <a href="{{ route('admin.staff.index') }}" class="text-sm text-slate-500 hover:text-blue-600 transition">← {{ __('Back to Staff') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <p class="text-sm text-slate-600 mb-4">{{ __('Staff can log in and create/shoot campaigns. When they shoot a campaign, their name and the date/time will be recorded.') }}</p>

                <form method="POST" action="{{ route('admin.staff.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">{{ __('Name') }}</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">{{ __('Email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">{{ __('Password') }}</label>
                        <input type="password" name="password" id="password" required
                               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">{{ __('Confirm password') }}</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="pt-2">
                        <p class="text-sm font-medium text-slate-800">{{ __('Permissions') }}</p>
                        <p class="mt-1 text-xs text-slate-600">{{ __('Select which sections this staff member can access.') }}</p>

                        <div class="mt-3 space-y-2">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="can_access_campaigns" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked>
                                {{ __('Campaigns') }}
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="can_access_templates" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked>
                                {{ __('Templates') }}
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="can_access_students" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked>
                                {{ __('Students') }}
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="can_access_schools" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked>
                                {{ __('Schools / Sessions / Classes / Imports') }}
                            </label>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700">
                            {{ __('Add staff') }}
                        </button>
                        <a href="{{ route('admin.staff.index') }}" class="inline-flex items-center px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
