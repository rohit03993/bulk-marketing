<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Auto WhatsApp After Call') }}
            </h2>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-500 hover:text-blue-600 transition">← {{ __('Back to Admin') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-100 p-4 text-sm text-emerald-900">{{ session('success') }}</div>
            @endif

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <p class="text-sm text-slate-600 mb-5">
                    {{ __('When enabled, a WhatsApp message is automatically sent to the student after every connected outgoing call. The selected template must have 3 parameters: Student Name, Caller Name, Caller Phone.') }}
                </p>

                <form method="POST" action="{{ route('admin.settings.postcall-whatsapp.update') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="enabled" class="block text-sm font-semibold text-slate-800">{{ __('Auto-send WhatsApp') }}</label>
                        <p class="text-xs text-slate-500 mb-2">{{ __('Fires after every connected outgoing call') }}</p>
                        <select name="enabled" id="enabled" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="0" @selected(! $enabled)>{{ __('Disabled') }}</option>
                            <option value="1" @selected($enabled)>{{ __('Enabled') }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="template_id" class="block text-sm font-medium text-slate-700">{{ __('WhatsApp Template') }}</label>
                        <select name="template_id" id="template_id"
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('— Select template —') }}</option>
                            @foreach ($templates as $t)
                                <option value="{{ $t->id }}" @selected($t->id == $templateId)>
                                    {{ $t->name }} ({{ $t->param_count }} {{ \Illuminate\Support\Str::plural('param', $t->param_count) }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Template params: {1} = Student Name, {2} = Caller Name, {3} = Caller Phone') }}</p>
                        @error('template_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700">
                            {{ __('Save settings') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
