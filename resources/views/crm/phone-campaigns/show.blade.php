<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('students.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Campaigns sent to') }} {{ $displayPhone }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if ($recipients->isEmpty())
                <div class="bg-white rounded-lg shadow-sm p-6 text-center text-gray-500">
                    {{ __('No campaigns have been sent to this number yet.') }}
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-4 py-3 border-b border-gray-200 font-medium text-gray-700">{{ __('Campaign sends') }}</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Campaign') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Student') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Date') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">{{ __('Message') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($recipients as $r)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 text-sm">
                                            <a href="{{ route('campaigns.show', $r->campaign) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $r->campaign->name }}</a>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-600">{{ $r->student?->name ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-600">{{ $r->created_at->format('M j, Y H:i') }}</td>
                                        <td class="px-4 py-2">
                                            @if ($r->status === 'sent')
                                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ __('Sent') }}</span>
                                            @elseif ($r->status === 'failed')
                                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">{{ __('Failed') }}</span>
                                            @else
                                                <span class="text-gray-600 text-sm">{{ $r->status }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-600 max-w-xs hidden sm:table-cell">
                                            @if (!empty($r->message_sent))
                                                {{ Str::limit($r->message_sent, 50) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200">{{ $recipients->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
