<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Campaigns') }}</h2>
            <a href="{{ route('campaigns.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                {{ __('New Campaign') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <form method="GET" action="{{ route('campaigns.index') }}" class="mb-4 flex flex-wrap gap-2 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500">{{ __('School') }}</label>
                    <select name="school_id" data-school-search="1" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($schools as $s)
                            <option value="{{ $s->id }}" {{ request('school_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">{{ __('Status') }}</label>
                    <select name="status" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">{{ __('All') }}</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                        <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>{{ __('Queued') }}</option>
                        <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>{{ __('Running') }}</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ __('Failed') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">{{ __('Template') }}</label>
                    <select name="template_id" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($templates as $t)
                            <option value="{{ $t->id }}" {{ (int) request('template_id', 0) === (int) $t->id ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-3 py-1.5 bg-gray-200 rounded-md text-sm">{{ __('Filter') }}</button>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($campaigns->isEmpty())
                    <div class="p-6 text-gray-500 text-center">
                        {{ __('No campaigns yet.') }}
                        <a href="{{ route('campaigns.create') }}" class="text-indigo-600 hover:underline ml-1">{{ __('Create one') }}</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('School') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Template') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Sent / Total') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($campaigns as $c)
                                    <tr class="hover:bg-gray-50" data-campaign-id="{{ $c->id }}">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $c->name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $c->school->name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $c->template->name }}</td>
                                        <td class="px-4 py-3 text-sm" data-stat="sent-total">{{ $realCounts[$c->id] ?? $c->sent_count }} / {{ $c->total_recipients }}</td>
                                        <td class="px-4 py-3" data-stat="status">
                                            @if ($c->status === 'completed')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ __('Completed') }}</span>
                                            @elseif ($c->status === 'running' || $c->status === 'queued')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $c->status }}</span>
                                            @elseif ($c->status === 'draft')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">{{ __('Draft') }}</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">{{ $c->status }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('campaigns.show', $c) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">{{ __('View') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200">{{ $campaigns->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            const rows = document.querySelectorAll('tbody tr[data-campaign-id]');
            const ids = Array.from(rows).map(r => r.dataset.campaignId).filter(Boolean);
            if (ids.length === 0) return;
            const statsUrl = '{{ route("campaigns.stats.bulk") }}?ids[]=' + ids.join('&ids[]=');
            const statusLabels = { completed: '{{ __("Completed") }}', running: '{{ __("Running") }}', queued: '{{ __("Queued") }}', draft: '{{ __("Draft") }}', failed: '{{ __("Failed") }}' };
            const poll = () => {
                fetch(statsUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => {
                        rows.forEach(row => {
                            const id = row.dataset.campaignId;
                            const c = data[id];
                            if (!c) return;
                            const sentTotal = row.querySelector('[data-stat="sent-total"]');
                            const statusCell = row.querySelector('[data-stat="status"]');
                            if (sentTotal) sentTotal.textContent = c.sent_count + ' / ' + c.total_recipients;
                            if (statusCell) {
                                const label = statusLabels[c.status] || c.status;
                                if (c.status === 'completed') {
                                    statusCell.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">' + label + '</span>';
                                } else if (c.status === 'running' || c.status === 'queued') {
                                    statusCell.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">' + label + '</span>';
                                } else if (c.status === 'draft') {
                                    statusCell.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">' + label + '</span>';
                                } else {
                                    statusCell.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">' + label + '</span>';
                                }
                            }
                        });
                        const hasActive = Object.values(data).some(c => c.status === 'running' || c.status === 'queued');
                        if (hasActive) setTimeout(poll, 3000);
                    })
                    .catch(() => setTimeout(poll, 5000));
            };
            const hasActiveRow = Array.from(rows).some(r => {
                const statusCell = r.querySelector('[data-stat="status"]');
                return statusCell && (statusCell.textContent.includes('Running') || statusCell.textContent.includes('Queued'));
            });
            if (hasActiveRow) setTimeout(poll, 3000);
        })();
    </script>
</x-app-layout>
