<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <a href="{{ route('campaigns.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $campaign->name }}</h2>
            </div>
            @if ($campaign->status === 'draft' && $campaign->recipients()->where('status', 'pending')->exists())
                <form method="POST" action="{{ route('campaigns.shoot', $campaign) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                        {{ __('Shoot campaign') }}
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-6" data-campaign-id="{{ $campaign->id }}" data-stats-url="{{ route('campaigns.stats', $campaign) }}">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('info'))
                <div class="rounded-md bg-blue-50 p-4 text-sm text-blue-800">{{ session('info') }}</div>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4" id="campaign-stats">
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase">{{ __('Total') }}</p>
                    <p class="text-2xl font-semibold text-gray-900" data-stat="total_recipients">{{ $campaign->total_recipients }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase">{{ __('Sent') }}</p>
                    <p class="text-2xl font-semibold text-green-600" data-stat="sent_count">{{ $campaign->sent_count }}</p>
                </div>
                @if (($campaign->failed_count ?? 0) > 0)
                <div class="bg-white rounded-lg shadow p-4" data-stat-wrap="failed_count">
                    <p class="text-xs font-medium text-gray-500 uppercase">{{ __('Failed') }}</p>
                    <p class="text-2xl font-semibold text-red-600" data-stat="failed_count">{{ $campaign->failed_count }}</p>
                </div>
                @endif
                @if (($pendingCount ?? 0) > 0)
                <div class="bg-white rounded-lg shadow p-4" data-stat-wrap="pending_count">
                    <p class="text-xs font-medium text-gray-500 uppercase">{{ __('Pending') }}</p>
                    <p class="text-2xl font-semibold text-amber-600" data-stat="pending_count">{{ $pendingCount }}</p>
                </div>
                @endif
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</p>
                    <p class="text-lg font-medium text-gray-900 capitalize" data-stat="status">{{ $campaign->status }}</p>
                </div>
            </div>

            @if ($campaign->shot_at && $campaign->shotByUser)
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-sm text-slate-700">
                    {{ __('Shot by') }} <strong>{{ $campaign->shotByUser->name }}</strong> {{ __('on') }} {{ $campaign->shot_at->format('d M Y, h:i A') }}
                </div>
            @endif

            @if ($pendingCount > 0)
                <div id="campaign-progress-section" class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                        {{ __('Sending in progress. Counts update automatically.') }}
                    </div>
                    @if ($campaign->status === 'queued' && $campaign->sent_count === 0 && $campaign->failed_count === 0)
                    <div class="bg-amber-50 border border-amber-300 rounded-lg p-4 text-sm text-amber-900">
                        <p class="font-medium">{{ __('Queue not running?') }}</p>
                        <p class="mt-1">{{ __('Messages are sent by a background queue. If nothing is sending, open a terminal in the project folder and run:') }}</p>
                        <code class="mt-2 block bg-white px-3 py-2 rounded border border-amber-200 font-mono text-xs">php artisan queue:work</code>
                        <p class="mt-2 text-xs">{{ __('Leave the terminal open. Sending will start within seconds. On a server, set up cron to run') }} <code class="bg-white px-1 rounded">schedule:run</code> {{ __('every minute.') }}</p>
                    </div>
                    @endif
                </div>
            @endif

            @if ($campaign->failed_count > 0 && $campaign->template)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-800">
                    <p class="font-medium">{{ __('Why did sending fail?') }}</p>
                    <p class="mt-1">{{ __('This campaign uses the Aisensy template') }} <strong>{{ $campaign->template->name }}</strong>. {{ __('"Campaign does not exist" means that name does not match any approved template in your Aisensy / WhatsApp Business account.') }}</p>
                    <p class="mt-2 text-xs">{{ __('Fix: In Aisensy dashboard, create or approve a template with the exact same name, or edit the template here (Templates) to match an existing Aisensy template name.') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-4 py-3 border-b border-gray-200 font-medium text-gray-700">{{ __('Recipients') }}</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Student') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Class') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Phone') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Message sent') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">{{ __('Error') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($recipients as $r)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $r->student?->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-600">{{ $r->student?->classSection?->full_name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-600">{{ $r->phone }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700 max-w-md">
                                        @if (!empty($r->message_sent))
                                            <span class="text-gray-800 whitespace-pre-wrap">{{ e($r->message_sent) }}</span>
                                        @elseif ($r->template_params !== null && count($r->template_params) > 0)
                                            <span class="text-gray-600 font-medium">{{ $campaign->template?->name ?? '—' }}</span>
                                            <span class="block text-xs text-gray-500 mt-0.5">{{ implode(' · ', array_map('e', $r->template_params)) }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">
                                        @if ($r->status === 'sent')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ __('Sent') }}</span>
                                        @elseif ($r->status === 'failed')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">{{ __('Failed') }}</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">{{ $r->status }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-xs text-gray-500 hidden sm:table-cell">{{ Str::limit($r->error_message, 40) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-200">{{ $recipients->links() }}</div>
            </div>
        </div>
    </div>

    @if (in_array($campaign->status, ['queued', 'running']) || ($pendingCount ?? 0) > 0)
    <script>
        (function () {
            const el = document.querySelector('[data-stats-url]');
            if (!el) return;
            const statsUrl = el.dataset.statsUrl;
            const set = (attr, value) => {
                const node = document.querySelector('[data-stat="' + attr + '"]');
                if (node) node.textContent = value;
            };
            const poll = () => {
                fetch(statsUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        set('sent_count', data.sent_count);
                        set('failed_count', data.failed_count);
                        set('total_recipients', data.total_recipients);
                        set('status', data.status);
                        if (data.pending_count !== undefined) set('pending_count', data.pending_count);
                        if ((data.pending_count || 0) === 0) {
                            document.querySelector('[data-stat-wrap="pending_count"]')?.remove();
                        }
                        if ((data.failed_count || 0) === 0) {
                            document.querySelector('[data-stat-wrap="failed_count"]')?.remove();
                        }
                        const progressSection = document.getElementById('campaign-progress-section');
                        if (progressSection && (data.pending_count || 0) === 0) progressSection.remove();
                        if (data.status === 'completed' || data.status === 'failed') {
                            window.location.reload();
                        } else {
                            setTimeout(poll, 2000);
                        }
                    })
                    .catch(() => setTimeout(poll, 3000));
            };
            poll();
        })();
    </script>
    @endif
</x-app-layout>
