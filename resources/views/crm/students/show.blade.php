<x-app-layout>
    <x-slot name="header">
        @php
            $lead = $student->lead_status ?? 'lead';
            $leadLabel = [
                'lead' => __('Uncalled'),
                'interested' => __('Interested'),
                'not_interested' => __('Not Interested'),
                'walkin_done' => __('Walk-in Done'),
                'admission_done' => __('Admission Done'),
                'follow_up_later' => __('Follow-up Later'),
            ][$lead] ?? ucfirst(str_replace('_',' ', $lead));
            $leadChip = match ($lead) {
                'interested' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'follow_up_later' => 'bg-amber-100 text-amber-900 border-amber-200',
                'walkin_done' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                'admission_done' => 'bg-green-100 text-green-900 border-green-200',
                'not_interested' => 'bg-rose-100 text-rose-900 border-rose-200',
                default => 'bg-sky-100 text-sky-900 border-sky-200',
            };
        @endphp
        <div class="rounded-2xl border border-slate-200 bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-600 text-white shadow-sm px-4 py-4 sm:px-6 sm:py-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h2 class="font-semibold text-xl leading-tight truncate">{{ $student->name }}</h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $leadChip }} bg-white/90">
                            {{ $leadLabel }}
                        </span>
                    </div>
                    <div class="mt-1 text-xs text-white/90">
                        @if ($student->father_name)
                            {{ __('S/o') }} {{ $student->father_name }} ·
                        @endif
                        {{ $student->classSection?->school?->name ?? '—' }}
                        @if ($student->classSection)
                            · {{ $student->classSection->full_name }}
                        @endif
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-white/15 border border-white/20 text-xs">
                            <span class="font-semibold">{{ (int) ($student->total_calls ?? 0) }}</span> {{ __('calls') }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-white/15 border border-white/20 text-xs">
                            <span class="font-semibold">{{ $student->next_followup_at ? $student->next_followup_at->format('d M') : '—' }}</span> {{ __('follow-up') }}
                        </span>
                    </div>
                </div>

                    <div class="flex flex-wrap gap-2">
                    <button type="button"
                        onclick="openQuickCallModal({{ $student->id }}, '{{ addslashes($student->name) }}', '{{ $phones[0] ?? '' }}', 'incoming')"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500 text-white text-xs font-semibold hover:bg-amber-600 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ __('Log Incoming Call') }}
                    </button>
                    <a href="{{ route('students.profile.edit', $student) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white text-slate-900 text-xs font-semibold hover:bg-slate-50">
                        {{ __('Edit') }}
                    </a>
                    <a href="{{ url()->previous() }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/15 border border-white/20 text-white text-xs font-semibold hover:bg-white/20">
                        {{ __('Back') }}
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-5 sm:py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
            @if (session('success'))
                <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4 text-sm text-emerald-900">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl bg-red-50 border border-red-100 p-4 text-sm text-red-900">{{ session('error') }}</div>
            @endif

            {{-- Mobile tabs --}}
            <div class="lg:hidden sticky top-0 z-10 -mx-4 sm:mx-0 px-4 sm:px-0 py-2 bg-slate-100/80 backdrop-blur border-b border-slate-200">
                <div class="bg-white border border-slate-200 rounded-xl p-1 flex gap-1">
                    <button type="button" class="js-profile-tab flex-1 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50" data-tab="overview">
                        {{ __('Overview') }}
                    </button>
                    <button type="button" class="js-profile-tab flex-1 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50" data-tab="calls">
                        {{ __('Calls') }}
                    </button>
                    <button type="button" class="js-profile-tab flex-1 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50" data-tab="messages">
                        {{ __('Messages') }}
                    </button>
                </div>
            </div>

            <div class="lg:grid lg:grid-cols-3 lg:gap-6 space-y-4 lg:space-y-0">
                {{-- Overview (Left on desktop) --}}
                <div id="sectionOverview" class="lg:col-span-1 space-y-4">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                        <h3 class="text-sm font-semibold text-slate-800">{{ __('Student details') }}</h3>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">{{ __('Lead status') }}</dt>
                                <dd class="text-slate-900 font-semibold">{{ $leadLabel }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">{{ __('Total calls') }}</dt>
                                <dd class="text-slate-900 font-semibold">{{ (int) ($student->total_calls ?? 0) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">{{ __('Last call') }}</dt>
                                <dd class="text-slate-900">{{ $student->last_call_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">{{ __('Next follow-up') }}</dt>
                                <dd class="text-slate-900">{{ $student->next_followup_at?->format('d M Y') ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                        <h3 class="text-sm font-semibold text-slate-800">{{ __('Phones & history') }}</h3>
                        <div class="mt-3 space-y-2">
                            @if (empty($phones))
                                <div class="text-sm text-gray-500">—</div>
                            @else
                                @foreach ($phones as $p)
                                    <div class="flex items-center justify-between gap-3 py-2 px-2 rounded-xl hover:bg-slate-50">
                                        <a href="#messages" data-phone="{{ $p }}"
                                           class="js-phone-to-messages text-sm font-medium text-gray-900 hover:text-indigo-700">
                                            {{ \App\Models\Student::formatPhoneForDisplay($p) }}
                                        </a>
                                        <button type="button"
                                            onclick="startCallAndLog({{ $student->id }}, '{{ addslashes($student->name) }}', '{{ $p }}', 'tel:{{ preg_replace('/\D+/', '', $p) }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            {{ __('Call & Log') }}
                                        </button>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                        <h3 class="text-sm font-semibold text-slate-800">{{ __('Tags') }}</h3>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($student->tags as $tag)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-800 border border-indigo-100">
                                    {{ $tag->name }}
                                </span>
                            @empty
                                <span class="text-sm text-gray-500">—</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                        <h3 class="text-sm font-semibold text-slate-800">{{ __('Assignment') }}</h3>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">{{ __('Assigned to') }}</dt>
                                <dd class="text-gray-900">{{ $student->assignedTo?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">{{ __('Assigned by') }}</dt>
                                <dd class="text-gray-900">{{ $student->assignedBy?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">{{ __('Assigned at') }}</dt>
                                <dd class="text-gray-900">{{ $student->assigned_at?->format('d M Y') ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- Calls --}}
                <div id="sectionCalls" class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-sm font-semibold text-slate-800">{{ __('Call history') }}</h3>
                            <span class="text-xs text-gray-500">{{ __('Latest 100') }}</span>
                        </div>
                        @if ($calls->isEmpty())
                            <div class="mt-4 text-sm text-gray-500 text-center py-6">{{ __('No calls logged yet.') }}</div>
                        @else
                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="text-xs text-slate-500">
                                        <tr class="border-b border-slate-200">
                                            <th class="text-left py-2 pr-4">{{ __('When') }}</th>
                                            <th class="text-left py-2 pr-4">{{ __('Result') }}</th>
                                            <th class="text-left py-2 pr-4">{{ __('Who') }}</th>
                                            <th class="text-left py-2 pr-4">{{ __('Duration') }}</th>
                                            <th class="text-left py-2">{{ __('Notes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($calls as $c)
                                            <tr class="align-top hover:bg-slate-50/70">
                                                <td class="py-2 pr-4 whitespace-nowrap text-gray-700">
                                                    <div>{{ $c->called_at?->format('d M Y, h:i A') ?? $c->created_at?->format('d M Y, h:i A') }}</div>
                                                    @if ($c->call_direction === 'incoming')
                                                        <span class="inline-flex items-center gap-0.5 mt-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">
                                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                                            {{ __('Incoming') }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-0.5 mt-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-sky-100 text-sky-800">
                                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                                            {{ __('Outgoing') }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-2 pr-4 whitespace-nowrap">
                                                    <div class="font-medium text-gray-900">
                                                        {{ \App\Models\StudentCall::$callStatuses[$c->call_status] ?? ucfirst(str_replace('_',' ', $c->call_status ?? '')) }}
                                                    </div>
                                                    @if ($c->status_changed_to)
                                                        <div class="text-xs text-gray-500">{{ __('Lead status:') }} {{ ucfirst(str_replace('_',' ', $c->status_changed_to)) }}</div>
                                                    @endif
                                                </td>
                                                <td class="py-2 pr-4 whitespace-nowrap text-gray-700">
                                                    {{ $c->user?->name ?? '—' }}
                                                </td>
                                                <td class="py-2 pr-4 whitespace-nowrap text-gray-700">
                                                    {{ $c->duration_minutes ? ($c->duration_minutes.' min') : '—' }}
                                                </td>
                                                <td class="py-2 text-gray-800">
                                                    {{ $c->call_notes ?: '—' }}
                                                    @if ($c->next_followup_at)
                                                        <div class="mt-1 text-xs text-gray-500">
                                                            {{ __('Follow-up:') }} {{ $c->next_followup_at?->format('d M Y') }}
                                                        </div>
                                                    @endif
                                                    @if (!empty($c->tags))
                                                        <div class="mt-2 flex flex-wrap gap-1">
                                                            @foreach ((array) $c->tags as $t)
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] bg-gray-100 text-gray-700">
                                                                    {{ ucfirst(str_replace('_',' ', $t)) }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Messages --}}
                <div id="sectionMessages" class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-800">{{ __('Send direct WhatsApp message') }}</h3>
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ __('Pick a phone + approved template to queue a Direct send.') }}
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('students.send-single', $student) }}" class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-600">{{ __('Phone') }}</label>
                                <select name="phone" id="sendPhoneSelect" class="mt-1 block w-full rounded-xl border-slate-200 text-sm" required>
                                    <option value="">{{ __('Select phone') }}</option>
                                    @foreach ($phones as $p)
                                        <option value="{{ $p }}">{{ \App\Models\Student::formatPhoneForDisplay($p) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600">{{ __('Template') }}</label>
                                <select name="aisensy_template_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm" required>
                                    <option value="">{{ __('Select template') }}</option>
                                    @foreach ($templates as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->param_count }} {{ \Illuminate\Support\Str::plural('param', $t->param_count) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-3 flex justify-end">
                                <button type="submit" class="inline-flex justify-center px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-fuchsia-600 text-white text-sm font-semibold hover:from-indigo-700 hover:to-fuchsia-700 shadow-sm">
                                    {{ __('Send WhatsApp') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <div id="messages" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 scroll-mt-24">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-sm font-semibold text-slate-800">{{ __('Messages (campaign + direct)') }}</h3>
                            <span class="text-xs text-gray-500">{{ __('Latest 100') }}</span>
                        </div>
                        @if ($messages->isEmpty())
                            <div class="mt-4 text-sm text-gray-500 text-center py-6">{{ __('No messages found for this student yet.') }}</div>
                        @else
                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="text-xs text-gray-500">
                                        <tr class="border-b">
                                            <th class="text-left py-2 pr-4">{{ __('When') }}</th>
                                            <th class="text-left py-2 pr-4">{{ __('Campaign') }}</th>
                                            <th class="text-left py-2 pr-4">{{ __('Status') }}</th>
                                            <th class="text-left py-2">{{ __('Message') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($messages as $m)
                                            @php
                                                $campName = $m->campaign?->name ?? '—';
                                                $isDirect = str_starts_with($campName, 'Direct:');
                                            @endphp
                                            <tr class="align-top">
                                                <td class="py-2 pr-4 whitespace-nowrap text-gray-700">
                                                    {{ $m->created_at?->format('d M Y, h:i A') ?? '—' }}
                                                </td>
                                                <td class="py-2 pr-4">
                                                    <div class="font-medium text-gray-900">
                                                        {{ $campName }}
                                                        @if ($isDirect)
                                                            <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-sky-100 text-sky-800">{{ __('DIRECT') }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ $m->campaign?->template?->name ?? '—' }}
                                                    </div>
                                                </td>
                                                <td class="py-2 pr-4 whitespace-nowrap">
                                                    @php
                                                        $status = $m->status ?? 'pending';
                                                        $badge = match ($status) {
                                                            'sent' => 'bg-emerald-100 text-emerald-800',
                                                            'failed' => 'bg-red-100 text-red-800',
                                                            'skipped' => 'bg-gray-100 text-gray-700',
                                                            default => 'bg-amber-100 text-amber-800',
                                                        };
                                                    @endphp
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $badge }}">
                                                        {{ strtoupper($status) }}
                                                    </span>
                                                    @if ($m->error_message)
                                                        <div class="mt-1 text-xs text-red-600">{{ $m->error_message }}</div>
                                                    @endif
                                                </td>
                                                <td class="py-2 text-gray-800">
                                                    {{ $m->message_sent ?: '—' }}
                                                    <div class="mt-1 text-xs text-gray-500">
                                                        {{ __('Phone:') }} {{ \App\Models\Student::formatPhoneForDisplay($m->phone) }}
                                                        @if ($m->campaign?->shotByUser)
                                                            · {{ __('By') }} {{ $m->campaign->shotByUser->name }}
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('crm.students.partials.log-call-modal')

    <script>
        function startCallAndLog(leadId, name, phone, telUri) {
            sessionStorage.setItem('pendingCallLog', JSON.stringify({
                leadId: leadId, name: name, phone: phone, setAt: Date.now()
            }));
            window.location.href = telUri;
        }
        window.afterCallLogSuccess = function() { location.reload(); };
    </script>

    <script>
        (function() {
            function isDesktop() {
                return window.matchMedia && window.matchMedia('(min-width: 1024px)').matches;
            }

            function setTab(tab) {
                if (isDesktop()) return;
                const tabs = ['overview','calls','messages'];
                tabs.forEach(t => {
                    const btn = document.querySelector('.js-profile-tab[data-tab="' + t + '"]');
                    if (btn) {
                        if (t === tab) {
                            btn.classList.add('bg-indigo-600','text-white');
                            btn.classList.remove('text-slate-700');
                        } else {
                            btn.classList.remove('bg-indigo-600','text-white');
                            btn.classList.add('text-slate-700');
                        }
                    }
                });
                const overview = document.getElementById('sectionOverview');
                const calls = document.getElementById('sectionCalls');
                const messages = document.getElementById('sectionMessages');
                if (overview) overview.style.display = (tab === 'overview') ? '' : 'none';
                if (calls) calls.style.display = (tab === 'calls') ? '' : 'none';
                if (messages) messages.style.display = (tab === 'messages') ? '' : 'none';
            }

            document.querySelectorAll('.js-profile-tab').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    setTab(btn.dataset.tab || 'overview');
                });
            });

            // Phone click: open Messages tab and preselect phone for sending.
            document.querySelectorAll('.js-phone-to-messages').forEach(function(a) {
                a.addEventListener('click', function() {
                    const phone = a.getAttribute('data-phone') || '';
                    const sel = document.getElementById('sendPhoneSelect');
                    if (sel && phone) sel.value = phone;
                    setTab('messages');
                });
            });

            // Initial tab.
            const hash = (window.location.hash || '').toLowerCase();
            if (hash === '#messages') setTab('messages');
            else setTab('overview');
        })();
    </script>
</x-app-layout>

