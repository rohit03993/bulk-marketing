<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Academic Sessions') }}</h2>
            <a href="{{ route('sessions.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                {{ __('Add Session') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($sessions->isEmpty())
                    <div class="p-6 text-gray-500 text-center">
                        {{ __('No sessions yet.') }}
                        <a href="{{ route('sessions.create') }}" class="text-indigo-600 hover:underline ml-1">{{ __('Add one') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Current') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($sessions as $session)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $session->name }}</td>
                                        <td class="px-4 py-3">
                                            @if ($session->is_current)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ __('Yes') }}</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('sessions.edit', $session) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">{{ __('Edit') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200">{{ $sessions->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
