<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ __('Lead Class Presets') }}</h2>
                <p class="text-xs text-slate-500 mt-1">{{ __('These presets appear in Tellcaller My Leads -> Add Lead (class selection).') }}</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-500 hover:text-blue-600 transition">← {{ __('Admin dashboard') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                <h3 class="font-semibold text-slate-800 mb-4">{{ __('Add / Enable a preset') }}</h3>
                <form method="POST" action="{{ route('admin.lead-class-presets.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('Grade') }}</label>
                            <select name="grade" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @foreach ([1,2,3,4,5,6,7,8,9,10,11,12,13] as $g)
                                    <option value="{{ $g }}" {{ old('grade', '11') == $g ? 'selected' : '' }}>{{ $g }}</option>
                                @endforeach
                            </select>
                            @error('grade')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('Stream') }}</label>
                            <select name="stream" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="" {{ old('stream') === '' ? 'selected' : '' }}>{{ __('No stream') }}</option>
                                <option value="NEET" {{ old('stream') === 'NEET' ? 'selected' : '' }}>NEET</option>
                                <option value="JEE" {{ old('stream') === 'JEE' ? 'selected' : '' }}>JEE</option>
                            </select>
                            @error('stream')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('Active?') }}</label>
                            <select name="is_active" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="1">{{ __('Yes') }}</option>
                                <option value="0">{{ __('No') }}</option>
                            </select>
                            @error('is_active')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        {{ __('Save preset') }}
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                <h3 class="font-semibold text-slate-800 mb-4">{{ __('Current presets') }}</h3>
                <div class="space-y-3">
                    @foreach ($presets as $p)
                        <div class="flex items-center justify-between gap-4 p-3 rounded-xl border border-slate-100 bg-slate-50">
                            <div>
                                <p class="font-medium text-slate-800">{{ $p->display_label }}</p>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ __('Status') }}: {{ $p->is_active ? __('Active') : __('Inactive') }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('admin.lead-class-presets.toggle', $p) }}">
                                @csrf
                                <input type="hidden" name="is_active" value="{{ $p->is_active ? 0 : 1 }}">
                                <button type="submit"
                                        class="text-xs px-3 py-2 rounded-md {{ $p->is_active ? 'bg-amber-600 text-white hover:bg-amber-700' : 'bg-emerald-600 text-white hover:bg-emerald-700' }}">
                                    {{ $p->is_active ? __('Deactivate') : __('Activate') }}
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

