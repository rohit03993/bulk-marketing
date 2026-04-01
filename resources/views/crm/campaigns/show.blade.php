<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <a href="{{ route('campaigns.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $campaign->name }}</h2>
            </div>
            <div class="flex items-center gap-2">
                @if ($campaign->status === 'draft' && $campaign->recipients()->where('status', 'pending')->exists())
                    <div class="inline-flex flex-col items-end gap-2">
                        <button type="button" id="open_shoot_options" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                            {{ __('Save') }}
                        </button>
                        <form method="POST" action="{{ route('campaigns.shoot', $campaign) }}" id="shoot_options_panel" class="hidden bg-white border border-slate-200 rounded-lg p-3 shadow-sm w-[560px] max-w-[90vw]">
                            @csrf
                            <p class="text-xs font-semibold text-slate-700 mb-2">{{ __('Dispatch options') }}</p>
                            <div class="flex flex-wrap gap-4 items-center">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="dispatch_mode" value="immediate" id="dispatch_mode_immediate" {{ old('dispatch_mode', 'immediate') === 'immediate' ? 'checked' : '' }}>
                                    {{ __('Shoot immediately') }}
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="dispatch_mode" value="scheduled" id="dispatch_mode_scheduled" {{ old('dispatch_mode') === 'scheduled' ? 'checked' : '' }}>
                                    {{ __('Schedule') }}
                                </label>
                            </div>
                            <div id="scheduled_fields" class="hidden mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[11px] text-gray-500">{{ __('Date & time (IST)') }}</label>
                                    <input type="datetime-local" name="scheduled_for" id="scheduled_for" value="{{ old('scheduled_for') }}" class="mt-1 rounded-md border-gray-300 text-sm w-full">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-gray-500">{{ __('Messages per run') }}</label>
                                    <input type="number" min="1" max="500" name="scheduled_batch_size" value="{{ old('scheduled_batch_size', (int) ($campaignBatchSize ?? 10)) }}" class="mt-1 rounded-md border-gray-300 text-sm w-full">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-gray-500">{{ __('Gap minutes') }}</label>
                                    <input type="number" min="0" max="1440" name="scheduled_delay_minutes" value="{{ old('scheduled_delay_minutes', (int) ($campaignDelayMinutes ?? 5)) }}" class="mt-1 rounded-md border-gray-300 text-sm w-full">
                                </div>
                            </div>
                            <div class="mt-3 flex justify-end gap-2">
                                <button type="button" id="cancel_shoot_options" class="inline-flex items-center px-3 py-2 bg-slate-100 text-slate-700 text-xs font-medium rounded-md hover:bg-slate-200">
                                    {{ __('Cancel') }}
                                </button>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-xs font-semibold rounded-md hover:bg-green-700">
                                    {{ __('Shoot') }}
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
                @if (in_array($campaign->status, ['queued', 'running']) && $campaign->recipients()->where('status', 'pending')->exists())
                    <form method="POST" action="{{ route('campaigns.stop', $campaign) }}" class="inline" onsubmit="return confirm('{{ __('Pause this campaign? Current message in processing may complete, then sending will stop.') }}')">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-md hover:bg-amber-700">
                            {{ __('Stop') }}
                        </button>
                    </form>
                @endif
                @if ($campaign->status === 'paused' && $campaign->recipients()->where('status', 'pending')->exists())
                    <form method="POST" action="{{ route('campaigns.resume', $campaign) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            {{ __('Resume / Resend Pending') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div
        class="py-6"
        data-campaign-id="{{ $campaign->id }}"
        data-stats-url="{{ route('campaigns.stats', $campaign) }}"
        data-batch-size="{{ (int) ($campaignBatchSize ?? 10) }}"
        data-batch-delay-minutes="{{ (int) ($campaignDelayMinutes ?? 5) }}"
        data-started-at="{{ optional($campaign->started_at ?? $campaign->shot_at)->toIso8601String() }}"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('info'))
                <div class="rounded-md bg-blue-50 p-4 text-sm text-blue-800">{{ session('info') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-disc ml-5">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
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
                    @if ($campaign->scheduled_at)
                        <div class="mt-1 text-indigo-700">
                            {{ __('Scheduled for') }} <strong>{{ $campaign->scheduled_at->copy()->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</strong> {{ __('(IST)') }}
                        </div>
                    @endif
                </div>
            @endif

            @if (!empty($campaign->media_url))
                <div class="bg-violet-50 border border-violet-200 rounded-lg p-3 text-sm text-violet-900">
                    <p class="font-medium">{{ __('Campaign media') }}</p>
                    <p class="mt-1">
                        <span class="font-medium">{{ __('Type:') }}</span>
                        {{ ucfirst((string) ($campaign->media_type ?? 'media')) }}
                        @if (!empty($campaign->media_filename))
                            · <span class="font-medium">{{ __('File:') }}</span> {{ $campaign->media_filename }}
                        @endif
                    </p>
                    <p class="mt-1 break-all">
                        <span class="font-medium">{{ __('URL:') }}</span>
                        <a href="{{ $campaign->media_url }}" target="_blank" rel="noopener" class="underline hover:text-violet-700">
                            {{ $campaign->media_url }}
                        </a>
                    </p>
                </div>
            @endif

            @if ($campaign->status === 'completed' && $campaign->started_at && $campaign->finished_at)
                @php
                    // Strict duration math: end timestamp minus start timestamp.
                    $durationSeconds = max(0, $campaign->finished_at->getTimestamp() - $campaign->started_at->getTimestamp());
                    $durationHours = intdiv($durationSeconds, 3600);
                    $durationMinutes = intdiv($durationSeconds % 3600, 60);
                    $durationRemainSeconds = $durationSeconds % 60;
                    $durationLabel = '';
                    if ($durationHours > 0) {
                        $durationLabel .= $durationHours . 'h ';
                    }
                    if ($durationMinutes > 0 || $durationHours > 0) {
                        $durationLabel .= $durationMinutes . 'm ';
                    }
                    $durationLabel .= $durationRemainSeconds . 's';
                @endphp
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-900">
                    <p class="font-semibold">{{ __('Campaign timing summary') }}</p>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs sm:text-sm">
                        <p><span class="font-medium">{{ __('Start time:') }}</span> {{ $campaign->started_at->format('d M Y, h:i A') }}</p>
                        <p><span class="font-medium">{{ __('End time:') }}</span> {{ $campaign->finished_at->format('d M Y, h:i A') }}</p>
                        <p><span class="font-medium">{{ __('Total time:') }}</span> {{ trim($durationLabel) }}</p>
                    </div>
                </div>
            @endif

            @if ($pendingCount > 0)
                <div id="campaign-progress-section" class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                        {{ __('Sending in progress. Counts update automatically.') }}
                    </div>
                    @if ($showBulkTiming)
                        <div id="campaign-timing-estimate" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 text-sm text-indigo-900">
                            <p class="font-medium">{{ __('Bulk sending schedule') }}</p>
                            <p class="mt-1">
                                {{ __('Configured pace:') }}
                                <strong>{{ (int) ($campaignBatchSize ?? 10) }}</strong>
                                {{ __('messages per run,') }}
                                <strong>{{ (int) ($campaignDelayMinutes ?? 5) }}</strong>
                                {{ __('minute gap before the next run.') }}
                            </p>
                            <p class="mt-1">
                                {{ __('Estimated remaining runs:') }}
                                <strong id="campaign-estimated-runs">{{ max(1, (int) ceil((int) ($pendingCount ?? 0) / max(1, (int) ($campaignBatchSize ?? 10)))) }}</strong>
                                ·
                                {{ __('Estimated minimum wait (gaps only):') }}
                                <strong id="campaign-estimated-wait">
                                    @php
                                        $initialRuns = max(1, (int) ceil((int) ($pendingCount ?? 0) / max(1, (int) ($campaignBatchSize ?? 10))));
                                        $initialGapMinutes = max(0, $initialRuns - 1) * (int) ($campaignDelayMinutes ?? 5);
                                    @endphp
                                    {{ $initialGapMinutes }} {{ __('min') }}
                                </strong>
                            </p>
                            <p class="mt-1 text-xs text-indigo-800/80">
                                {{ __('This estimate is for bulk campaigns only and excludes provider/API processing time.') }}
                            </p>
                            <p class="mt-1">
                                {{ __('Estimated completion at (IST):') }}
                                <strong id="campaign-estimated-completion-at">—</strong>
                            </p>
                        </div>
                    @endif
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
            const panel = document.getElementById('shoot_options_panel');
            const openBtn = document.getElementById('open_shoot_options');
            const cancelBtn = document.getElementById('cancel_shoot_options');
            const modeImmediate = document.getElementById('dispatch_mode_immediate');
            const modeScheduled = document.getElementById('dispatch_mode_scheduled');
            const wrap = document.getElementById('scheduled_fields');
            const dt = document.getElementById('scheduled_for');
            if (!wrap || !dt) return;
            const now = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            const localMin = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
            dt.min = localMin;

            const sync = function () {
                const scheduled = !!modeScheduled && modeScheduled.checked;
                wrap.classList.toggle('hidden', !scheduled);
                dt.required = scheduled;
            };
            modeImmediate && modeImmediate.addEventListener('change', sync);
            modeScheduled && modeScheduled.addEventListener('change', sync);
            openBtn && openBtn.addEventListener('click', function () {
                panel.classList.remove('hidden');
            });
            cancelBtn && cancelBtn.addEventListener('click', function () {
                panel.classList.add('hidden');
            });
            @if($errors->has('scheduled_for') || $errors->has('scheduled_batch_size') || $errors->has('scheduled_delay_minutes'))
            panel && panel.classList.remove('hidden');
            @endif
            sync();
        })();
    </script>
    <script>
        (function () {
            const el = document.querySelector('[data-stats-url]');
            if (!el) return;
            const statsUrl = el.dataset.statsUrl;
            const batchSize = Math.max(1, parseInt(el.dataset.batchSize || '10', 10));
            const batchDelayMinutes = Math.max(0, parseInt(el.dataset.batchDelayMinutes || '0', 10));
            const startedAtIso = el.dataset.startedAt || '';
            const set = (attr, value) => {
                const node = document.querySelector('[data-stat="' + attr + '"]');
                if (node) node.textContent = value;
            };
            const formatDateTimeIST = (d) => new Intl.DateTimeFormat('en-IN', {
                timeZone: 'Asia/Kolkata',
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true,
            }).format(d);
            const updateTimingEstimate = (pendingCount) => {
                const runsNode = document.getElementById('campaign-estimated-runs');
                const waitNode = document.getElementById('campaign-estimated-wait');
                const completionAtNode = document.getElementById('campaign-estimated-completion-at');
                if (!runsNode || !waitNode || !completionAtNode) return;
                if (pendingCount <= 0) {
                    runsNode.textContent = '0';
                    waitNode.textContent = '0 min';
                    completionAtNode.textContent = '{{ __("Completed") }}';
                    return;
                }
                const runs = Math.ceil(pendingCount / batchSize);
                const gapMinutes = Math.max(0, runs - 1) * batchDelayMinutes;
                runsNode.textContent = String(runs);
                waitNode.textContent = String(gapMinutes) + ' min';

                const start = startedAtIso ? new Date(startedAtIso) : null;
                if (!start || Number.isNaN(start.getTime())) {
                    completionAtNode.textContent = '—';
                    return;
                }

                // Use live remaining gap estimate so completion time doesn't drift into the past
                // while campaign still has pending recipients.
                const now = new Date();
                const estimatedEnd = new Date(now.getTime() + (gapMinutes * 60 * 1000));
                completionAtNode.textContent = formatDateTimeIST(estimatedEnd);
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
                        updateTimingEstimate(parseInt(data.pending_count || 0, 10));
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
