<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('sessions.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add Academic Session') }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('sessions.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="name" :value="__('Session name (e.g. 2024-25)')" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_current" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('is_current') ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-600">{{ __('Set as current session') }}</span>
                        </label>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <x-primary-button>{{ __('Create Session') }}</x-primary-button>
                        <a href="{{ route('sessions.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
