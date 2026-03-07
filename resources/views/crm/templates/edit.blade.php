<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('templates.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Template') }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('templates.update', $template) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <x-input-label for="name" :value="__('Template name (Aisensy)')" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $template->name)" required />
                    </div>
                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <x-text-input id="description" name="description" class="block mt-1 w-full" :value="old('description', $template->description)" />
                    </div>
                    <div>
                        <x-input-label for="body" :value="__('Message body (optional)')" />
                        <textarea id="body" name="body" rows="4" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Hello @{{1}}, thank you for registering at @{{2}}.">{{ old('body', $template->body) }}</textarea>
                        <p class="text-xs text-slate-500 mt-1">{{ __('Use') }} @{{1}}, @{{2}}, @{{3}}… {{ __('for parameters. Stored so campaign reports show the exact message sent.') }}</p>
                    </div>
                    <div>
                        <x-input-label for="param_count" :value="__('Number of parameters (0–4)')" />
                        <select id="param_count" name="param_count" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @for ($i = 0; $i <= 4; $i++)
                                <option value="{{ $i }}" {{ (int) old('param_count', $template->param_count) === $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    @php $sources = $template->getParamSources(); @endphp
                    @for ($i = 0; $i <= 4; $i++)
                        <div class="param-row" data-index="{{ $i }}" style="{{ $i >= (int) old('param_count', $template->param_count) ? 'display:none' : '' }}">
                            <x-input-label :for="'param_'.$i" :value="__('Param :n source', ['n' => $i + 1])" />
                            <select name="param_{{ $i }}" id="param_{{ $i }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($paramOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('param_'.$i, $sources[$i] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endfor
                    <div class="flex gap-3 pt-2">
                        <x-primary-button>{{ __('Update Template') }}</x-primary-button>
                        <a href="{{ route('templates.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('param_count').addEventListener('change', function() {
            var n = parseInt(this.value, 10);
            document.querySelectorAll('.param-row').forEach(function(row) {
                row.style.display = parseInt(row.dataset.index, 10) < n ? 'block' : 'none';
            });
        });
    </script>
</x-app-layout>
