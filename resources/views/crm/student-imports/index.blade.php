<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Student Imports') }}</h2>
            <a href="{{ route('student-imports.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                {{ __('New Import') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($imports->isEmpty())
                    <div class="p-6 text-gray-500 text-center">
                        {{ __('No imports yet.') }}
                        <a href="{{ route('student-imports.create') }}" class="text-indigo-600 hover:underline ml-1">{{ __('Upload Excel') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('File') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('School') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Rows') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($imports as $imp)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $imp->original_filename }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $imp->school->name }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $imp->processed_rows }} / {{ $imp->total_rows }}</td>
                                        <td class="px-4 py-3">
                                            @if ($imp->status === 'completed')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ __('Completed') }}</span>
                                            @elseif ($imp->status === 'failed')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">{{ __('Failed') }}</span>
                                            @elseif ($imp->status === 'mapping')
                                                <a href="{{ route('student-imports.mapping', $imp) }}" class="text-indigo-600 hover:underline text-sm">{{ __('Map columns') }}</a>
                                            @else
                                                <span class="text-gray-500">{{ $imp->status }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $imp->created_at->format('M j, Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200">{{ $imports->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
