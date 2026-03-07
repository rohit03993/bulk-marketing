<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('schools.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit School') }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('schools.update', $school) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <x-input-label for="name" :value="__('School name')" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $school->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="short_name" :value="__('Short name (optional)')" />
                        <x-text-input id="short_name" name="short_name" class="block mt-1 w-full" :value="old('short_name', $school->short_name)" />
                    </div>
                    <div>
                        <x-input-label for="address" :value="__('Address')" />
                        <textarea id="address" name="address" rows="2" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('address', $school->address) }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="city" :value="__('City')" />
                            <x-text-input id="city" name="city" class="block mt-1 w-full" :value="old('city', $school->city)" />
                        </div>
                        <div>
                            <x-input-label for="state" :value="__('State')" />
                            <x-text-input id="state" name="state" class="block mt-1 w-full" :value="old('state', $school->state)" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="contact_email" :value="__('Contact email (optional)')" />
                        <x-text-input id="contact_email" name="contact_email" type="email" class="block mt-1 w-full" :value="old('contact_email', $school->contact_email)" />
                    </div>
                    <div class="flex gap-3 pt-2">
                        <x-primary-button>{{ __('Update School') }}</x-primary-button>
                        <a href="{{ route('schools.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
