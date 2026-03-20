<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ __('Lead Categories') }}</h2>
                <p class="text-xs text-slate-500">{{ __('These categories show in Tellcaller "My Leads" -> Add Lead.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-500 hover:text-blue-600 transition">← {{ __('Admin dashboard') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                <h3 class="font-semibold text-slate-800 mb-4">{{ __('Add new category') }}</h3>

                <form method="POST" action="{{ route('admin.lead-categories.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('Category name') }}</label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            placeholder="{{ __('e.g. Ravi (Ready)') }}"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        {{ __('Add Category') }}
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                <h3 class="font-semibold text-slate-800 mb-4">{{ __('Existing categories') }}</h3>

                @if ($categories->isEmpty())
                    <p class="text-sm text-slate-500">{{ __('No categories yet. Add one above.') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($categories as $cat)
                            <div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50">
                                <div>
                                    <p class="font-medium text-slate-800">{{ $cat->name }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ __('Used by') }} {{ $counts[$cat->id] ?? 0 }} {{ __('students') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if (($counts[$cat->id] ?? 0) > 0)
                                        <span class="text-xs px-2 py-1 rounded-md bg-amber-50 text-amber-800 border border-amber-200">
                                            {{ __('In use') }}
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('admin.lead-categories.destroy', $cat) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-xs px-3 py-2 rounded-md bg-red-600 text-white hover:bg-red-700"
                                                    onclick="return confirm('{{ __('Delete this category?') }}')">
                                                {{ __('Delete') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

