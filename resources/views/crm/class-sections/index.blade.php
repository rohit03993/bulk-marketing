<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Classes & Sections') }}</h2>
            <a href="{{ route('class-sections.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                {{ __('Add Class/Section') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <form method="GET" action="{{ route('class-sections.index') }}" class="mb-4 flex flex-wrap gap-2 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500">{{ __('School') }}</label>
                    <select name="school_id" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($schools as $s)
                            <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">{{ __('Session') }}</label>
                    <select name="session_id" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($sessions as $s)
                            <option value="{{ $s->id }}" {{ request('session_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-3 py-1.5 bg-gray-200 rounded-md text-sm">{{ __('Filter') }}</button>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($classSections->isEmpty())
                    <div class="p-6 text-gray-500 text-center">
                        {{ __('No classes yet.') }}
                        <a href="{{ route('class-sections.create') }}" class="text-indigo-600 hover:underline ml-1">{{ __('Add one') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Class / Section') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">{{ __('School') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">{{ __('Session') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($classSections as $cs)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $cs->full_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 hidden sm:table-cell">{{ $cs->school->name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 hidden sm:table-cell">{{ $cs->academicSession->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('class-sections.edit', $cs) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">{{ __('Edit') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200">{{ $classSections->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
