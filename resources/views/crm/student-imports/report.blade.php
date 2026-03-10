<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('student-imports.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Import Report') }}</h2>
            </div>
            <a href="{{ route('student-imports.index') }}"
               class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                {{ __('Back to imports') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Summary --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/80">
                    <h3 class="font-semibold text-gray-800">{{ __('Import summary') }}</h3>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $import->original_filename }} · {{ $import->school->name }}</p>
                </div>
                <div class="p-5">
                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Total rows') }}</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $import->total_rows }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Processed') }}</dt>
                            <dd class="mt-1 text-2xl font-semibold text-emerald-600">{{ $import->processed_rows }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Skipped') }}</dt>
                            <dd class="mt-1 text-2xl font-semibold {{ $import->skipped_count > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ $import->skipped_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Status') }}</dt>
                            <dd class="mt-1">
                                @if($import->status === 'completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('Completed') }}</span>
                                @else
                                    <span class="text-gray-600">{{ $import->status }}</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                    <p class="mt-4 text-sm text-gray-600">
                        {{ __('Processed rows are either new students created or existing students matched by phone and tagged. Skipped rows could not be imported due to validation (see below).') }}
                    </p>
                </div>
            </div>

            {{-- Skipped rows (ignored numbers) --}}
            @if($import->skipped_count > 0 && !empty($import->skipped_rows))
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-amber-50/80">
                        <div>
                            <h3 class="font-semibold text-amber-900">{{ __('Skipped rows') }}</h3>
                            <p class="text-sm text-amber-700 mt-0.5">{{ __('These rows could not be imported. Fix the data in your file and re-import if needed.') }}</p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                            {{ $import->skipped_count }} {{ __('row(s)') }}
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Row #') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Name') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Phone') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Reason') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($import->skipped_rows as $entry)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $entry['row'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $entry['name'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            @if(!empty($entry['phone']))
                                                <code class="text-gray-800 bg-gray-100 px-1.5 py-0.5 rounded">{{ $entry['phone'] }}</code>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-amber-700">{{ $entry['reason'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                @if($import->skipped_count === 0)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
                        <p class="text-gray-600">{{ __('All rows were processed. No rows were skipped.') }}</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
