<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('WhatsApp Templates (Aisensy)') }}</h2>
            <a href="{{ route('templates.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                {{ __('Add Template') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($templates->isEmpty())
                    <div class="p-6 text-gray-500 text-center">
                        {{ __('No templates yet.') }}
                        <a href="{{ route('templates.create') }}" class="text-indigo-600 hover:underline ml-1">{{ __('Add one') }}</a>
                        {{ __('Template name must match the campaign name in Aisensy.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Params') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($templates as $t)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-gray-900">{{ $t->name }}</span>
                                            @if ($t->description)
                                                <p class="text-sm text-gray-500">{{ $t->description }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $t->param_count }} {{ __('params') }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('templates.edit', $t) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">{{ __('Edit') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200">{{ $templates->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
