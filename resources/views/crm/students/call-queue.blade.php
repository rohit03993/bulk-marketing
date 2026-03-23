<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __("Today's Calling Queue") }}</h2>
            <a href="{{ route('students.my-leads') }}"
               class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                {{ __('My leads') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if (session('info'))
                <div class="mb-4 rounded-md bg-blue-50 p-4 text-sm text-blue-800">{{ session('info') }}</div>
            @endif

            {{-- Hero stats --}}
            <div class="rounded-xl bg-gradient-to-br from-indigo-600 to-purple-700 text-white p-6 mb-6">
                <h3 class="text-lg font-semibold mb-1">👋 {{ __('Hello, :name!', ['name' => auth()->user()->name]) }}</h3>
                <p class="text-white/90 text-sm mb-4">
                    @if($stats['queue_count'] > 0)
                        {{ __('You have :count leads to call today. Let\'s get started!', ['count' => $stats['queue_count']]) }}
                    @else
                        🎉 {{ __('No pending calls! You\'re all caught up.') }}
                    @endif
                </p>
                <div class="flex flex-wrap gap-3 text-sm">
                    <span class="inline-flex items-center gap-1.5 bg-white/20 px-3 py-1.5 rounded-full">
                        <span class="font-medium">{{ $stats['calls_today'] }}</span> {{ __('calls today') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 bg-white/20 px-3 py-1.5 rounded-full">
                        <span class="font-medium">{{ $stats['connected_today'] }}</span> {{ __('connected') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 bg-white/20 px-3 py-1.5 rounded-full">
                        <span class="font-medium">{{ $stats['pending_followups'] }}</span> {{ __('follow-ups') }}
                    </span>
                </div>
                @if($stats['queue_count'] > 0)
                    <div class="mt-4 text-right">
                        <span class="text-4xl font-bold">{{ $stats['queue_count'] }}</span>
                        <span class="block text-sm text-white/80">{{ __('Pending calls') }}</span>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Current lead card --}}
                <div class="lg:col-span-2">
                    @if($queue->isNotEmpty())
                        @php
                            $current = $queue->first();
                            $phone = $current->whatsapp_phone_primary ?: $current->whatsapp_phone_secondary;
                            $phone = $phone ? preg_replace('/[^0-9]/', '', $phone) : '';
                            $phoneDisplay = $phone ? '+91' . substr($phone, -10) : '';
                            $status = $current->lead_status ?? 'lead';
                            $statusLabels = [
                                'lead' => __('Uncalled'),
                                'interested' => __('Interested'),
                                'not_interested' => __('Not Interested'),
                                'walkin_done' => __('Walk-in Done'),
                                'admission_done' => __('Admission Done'),
                                'follow_up_later' => __('Follow-up Later'),
                            ];
                            $statusLabel = $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status));
                        @endphp
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" id="currentLeadCard">
                            <div class="bg-emerald-600 text-white px-4 py-2 flex justify-between items-center">
                                <span class="font-medium">📞 {{ __('Now Calling') }}</span>
                                <span class="text-sm bg-white/20 px-2 py-0.5 rounded">#1 of {{ $queue->count() }}</span>
                            </div>
                            <div class="p-5">
                                <a id="currentLeadNameLink" href="{{ route('students.show', $current) }}" class="hover:text-indigo-700">
                                    <h3 class="text-xl font-bold text-gray-900" id="currentLeadName">{{ $current->name }}</h3>
                                </a>
                                <p class="text-sm text-gray-500" id="currentLeadFather" style="{{ $current->father_name ? '' : 'display:none' }}">
                                    {{ __('S/o') }} <span id="currentLeadFatherName">{{ $current->father_name }}</span>
                                </p>
                                <p class="mt-2 text-lg font-semibold text-emerald-600" id="currentLeadPhone">
                                    @if($phoneDisplay)
                                        <a href="tel:{{ $phone }}" class="inline-flex items-center gap-1">{{ $phoneDisplay }}</a>
                                    @else
                                        —
                                    @endif
                                </p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-sky-100 text-sky-800" id="currentLeadStatus">{{ $statusLabel }}</span>
                                    <span class="text-sm text-gray-600">{{ __('Total calls') }}: <strong id="currentLeadCalls">{{ (int) $current->total_calls }}</strong></span>
                                    <span class="text-sm {{ $current->next_followup_at && $current->next_followup_at->isPast() ? 'text-red-600 font-medium' : 'text-gray-600' }}"
                                          id="currentLeadFollowup"
                                          style="{{ $current->next_followup_at ? '' : 'display:none' }}">
                                        {{ __('Follow-up') }}:
                                        <span id="currentLeadFollowupText">{{ $current->next_followup_at ? $current->next_followup_at->format('d M, h:i A') : '' }}</span>
                                        <span id="currentLeadOverdueTag" style="{{ $current->next_followup_at && $current->next_followup_at->isPast() ? '' : 'display:none' }}">
                                            ({{ __('Overdue') }})
                                        </span>
                                    </span>
                                </div>
                                @if($current->last_call_notes)
                                    <div class="mt-3 p-2 bg-gray-50 rounded text-sm text-gray-600" id="currentLeadNotes">
                                        {{ __('Last notes') }}: {{ Str::limit($current->last_call_notes, 100) }}
                                    </div>
                                @endif
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button type="button" onclick="makeCall()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700">
                                        <span>📞</span> {{ __('Call Now') }}
                                    </button>
                                    <a id="sendTemplateBtn" href="{{ route('students.show', $current) }}#sectionMessages" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700">
                                        {{ __('Send template') }}
                                    </a>
                                    <button type="button" onclick="skipLead()" class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
                                        {{ __('Skip for now') }}
                                    </button>
                                    <a href="{{ route('students.edit', $current) }}" class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
                                        {{ __('View details') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="currentLeadId" value="{{ $current->id }}">
                        <input type="hidden" id="currentLeadNotConnectedAttempts" value="{{ (int) ($current->not_connected_attempts_count ?? 0) }}">
                    @else
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                            <div class="text-4xl mb-4">🎉</div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ __('All caught up!') }}</h3>
                            <p class="text-gray-500 mt-2">{{ __('You\'ve completed all your calls for today.') }}</p>
                            <a href="{{ route('students.my-leads') }}" class="inline-flex mt-4 px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-700">
                                {{ __('View my leads') }}
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Queue list + progress --}}
                <div class="space-y-4">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-4 py-2 border-b border-gray-100 flex justify-between items-center">
                            <span class="font-medium text-gray-800">{{ __('Call queue') }}</span>
                            <span class="text-sm text-gray-500">{{ $queue->count() }} {{ __('leads') }}</span>
                        </div>
                        <div class="max-h-80 overflow-y-auto" id="queueList">
                            @php
                                $statusLabels = [
                                    'lead' => __('Uncalled'),
                                    'interested' => __('Interested'),
                                    'not_interested' => __('Not Interested'),
                                    'walkin_done' => __('Walk-in Done'),
                                    'admission_done' => __('Admission Done'),
                                    'follow_up_later' => __('Follow-up Later'),
                                ];
                            @endphp
                            @foreach($queue as $idx => $s)
                                @php
                                    $ph = $s->whatsapp_phone_primary ?: $s->whatsapp_phone_secondary;
                                    $ph = $ph ? preg_replace('/[^0-9]/', '', $ph) : '';
                                    $st = $s->lead_status ?? 'lead';
                                    $stLabel = $statusLabels[$st] ?? ucfirst(str_replace('_',' ', $st));
                                @endphp
                                <div class="queue-item flex items-center gap-3 px-4 py-2.5 border-b border-gray-100 hover:bg-gray-50 cursor-pointer {{ $idx === 0 ? 'bg-emerald-50 border-l-4 border-l-emerald-500' : '' }}"
                                     data-lead-id="{{ $s->id }}"
                                     data-name="{{ $s->name }}"
                                     data-father="{{ $s->father_name }}"
                                     data-phone="{{ $ph ? '+91'.substr($ph,-10) : '' }}"
                                     data-status="{{ $stLabel }}"
                                     data-calls="{{ (int) $s->total_calls }}"
                                     data-followup="{{ $s->next_followup_at ? $s->next_followup_at->format('d M, h:i A') : '' }}"
                                     data-overdue="{{ $s->next_followup_at && $s->next_followup_at->isPast() ? '1' : '0' }}"
                                     data-not-connected-attempts="{{ (int) ($s->not_connected_attempts_count ?? 0) }}"
                                     data-notes="{{ $s->last_call_notes ? Str::limit($s->last_call_notes, 80) : '' }}"
                                     onclick="selectLead({{ $s->id }}, {{ $idx + 1 }})">
                                    <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold {{ $idx === 0 ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-700' }}">{{ $idx + 1 }}</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-gray-900 truncate">{{ $s->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $ph ? '+91 '.substr($ph,-10) : '—' }} · {{ $stLabel }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">{{ __("Today's progress") }}</h4>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div>
                                <div class="text-2xl font-bold text-gray-900">{{ $stats['calls_today'] }}</div>
                                <div class="text-xs text-gray-500">{{ __('Calls made') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-emerald-600">{{ $stats['connected_today'] }}</div>
                                <div class="text-xs text-gray-500">{{ __('Connected') }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-amber-600">{{ $stats['pending_followups'] }}</div>
                                <div class="text-xs text-gray-500">{{ __('Follow-ups') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('crm.students.partials.log-call-modal')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        });
        const currentLeadId = document.getElementById('currentLeadId')?.value || null;
        const queueTotal = {{ $queue->count() }};

        function makeCall() {
            const leadId = document.getElementById('currentLeadId')?.value;
            const name = document.getElementById('currentLeadName')?.textContent?.trim() || '';
            const phoneEl = document.getElementById('currentLeadPhone');
            let phone = '';
            const link = phoneEl?.querySelector('a[href^="tel:"]');
            if (link) phone = link.getAttribute('href').replace('tel:', '').replace(/\D/g, '');
            if (!phone && phoneEl) phone = phoneEl.textContent.replace(/\D/g, '');
            if (!phone) {
                alert('{{ __("No phone number for this lead.") }}');
                return;
            }
            try {
                const attempts = parseInt(document.getElementById('currentLeadNotConnectedAttempts')?.value || '0', 10) || 0;
                sessionStorage.setItem('pendingCallLog', JSON.stringify({
                    leadId: leadId,
                    name: name,
                    phone: '+91' + phone.slice(-10),
                    notConnectedAttempts: attempts,
                    setAt: Date.now()
                }));
            } catch (e) {}
            window.location.href = 'tel:' + phone;
        }

        function skipLead() {
            loadNextLead();
        }

        function selectLead(leadId, position) {
            document.getElementById('currentLeadId').value = leadId;
            const item = document.querySelector('.queue-item[data-lead-id="' + leadId + '"]');
            if (!item) return;
            document.getElementById('currentLeadName').textContent = item.dataset.name || '';
            const fatherWrap = document.getElementById('currentLeadFather');
            const fatherNameEl = document.getElementById('currentLeadFatherName');
            const father = (item.dataset.father || '').trim();
            if (fatherWrap && fatherNameEl) {
                fatherNameEl.textContent = father;
                fatherWrap.style.display = father ? 'block' : 'none';
            }
            const nameLink = document.getElementById('currentLeadNameLink');
            if (nameLink) nameLink.href = '{{ url("students") }}/' + leadId;
            const sendTemplateBtn = document.getElementById('sendTemplateBtn');
            if (sendTemplateBtn) sendTemplateBtn.href = '{{ url("students") }}/' + leadId + '#sectionMessages';
            const attemptsInput = document.getElementById('currentLeadNotConnectedAttempts');
            if (attemptsInput) attemptsInput.value = item.dataset.notConnectedAttempts || '0';
            const phone = item.dataset.phone || '';
            const phoneEl = document.getElementById('currentLeadPhone');
            if (phone) {
                phoneEl.innerHTML = '<a href="tel:' + phone.replace(/\D/g,'') + '" class="inline-flex items-center gap-1">' + phone + '</a>';
            } else {
                phoneEl.textContent = '—';
            }
            document.getElementById('currentLeadStatus').textContent = item.dataset.status || '';
            document.getElementById('currentLeadCalls').textContent = item.dataset.calls || '0';
            const followupEl = document.getElementById('currentLeadFollowup');
            const followupTextEl = document.getElementById('currentLeadFollowupText');
            const overdueTagEl = document.getElementById('currentLeadOverdueTag');
            if (followupEl && followupTextEl && overdueTagEl) {
                const f = (item.dataset.followup || '').trim();
                const isOverdue = (item.dataset.overdue || '0') === '1';
                followupTextEl.textContent = f;
                followupEl.style.display = f ? 'inline' : 'none';
                overdueTagEl.style.display = f && isOverdue ? 'inline' : 'none';
                followupEl.classList.toggle('text-red-600', isOverdue);
                followupEl.classList.toggle('font-medium', isOverdue);
                followupEl.classList.toggle('text-gray-600', !isOverdue);
            }
            const notesEl = document.getElementById('currentLeadNotes');
            if (notesEl) {
                notesEl.textContent = item.dataset.notes ? ('{{ __("Last notes") }}: ' + item.dataset.notes) : '';
                notesEl.style.display = item.dataset.notes ? 'block' : 'none';
            }
            document.querySelectorAll('.queue-item').forEach(el => {
                el.classList.remove('bg-emerald-50', 'border-l-4', 'border-l-emerald-500');
                el.querySelector('.w-8')?.classList.remove('bg-emerald-600', 'text-white');
                el.querySelector('.w-8')?.classList.add('bg-gray-200', 'text-gray-700');
            });
            item.classList.add('bg-emerald-50', 'border-l-4', 'border-l-emerald-500');
            item.querySelector('.w-8')?.classList.remove('bg-gray-200', 'text-gray-700');
            item.querySelector('.w-8')?.classList.add('bg-emerald-600', 'text-white');
            const badge = document.querySelector('#currentLeadCard .bg-white\\/20');
            if (badge) badge.textContent = '#' + position + ' of ' + queueTotal;
        }

        function loadNextLead() {
            const id = document.getElementById('currentLeadId')?.value;
            if (!id) return;
            fetch('{{ route("students.call-queue.next") }}?current_lead_id=' + id, {
                headers: { 'X-CSRF-TOKEN': window.csrfToken || '', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.has_next && data.lead) {
                    const L = data.lead;
                    document.getElementById('currentLeadId').value = L.id;
                    document.getElementById('currentLeadName').textContent = L.name;
                    const fatherWrap = document.getElementById('currentLeadFather');
                    const fatherNameEl = document.getElementById('currentLeadFatherName');
                    if (fatherWrap && fatherNameEl) {
                        const father = (L.father_name || '').trim();
                        fatherNameEl.textContent = father;
                        fatherWrap.style.display = father ? 'block' : 'none';
                    }
                    const nameLink = document.getElementById('currentLeadNameLink');
                    if (nameLink) nameLink.href = '{{ url("students") }}/' + L.id;
                    const sendTemplateBtn = document.getElementById('sendTemplateBtn');
                    if (sendTemplateBtn) sendTemplateBtn.href = '{{ url("students") }}/' + L.id + '#sectionMessages';
                    const attemptsInput = document.getElementById('currentLeadNotConnectedAttempts');
                    if (attemptsInput) attemptsInput.value = String(L.not_connected_attempts_count || 0);
                    const phoneEl = document.getElementById('currentLeadPhone');
                    if (L.mobile_number) {
                        phoneEl.innerHTML = '<a href="tel:' + (L.phone_raw || L.mobile_number).replace(/\D/g,'') + '" class="inline-flex items-center gap-1">' + L.mobile_number + '</a>';
                    } else {
                        phoneEl.textContent = '—';
                    }
                    document.getElementById('currentLeadStatus').textContent = L.status_label;
                    document.getElementById('currentLeadCalls').textContent = L.total_calls;
                    const followupEl = document.getElementById('currentLeadFollowup');
                    const followupTextEl = document.getElementById('currentLeadFollowupText');
                    const overdueTagEl = document.getElementById('currentLeadOverdueTag');
                    if (followupEl && followupTextEl && overdueTagEl) {
                        const f = (L.next_followup_at || '').trim();
                        const isOverdue = !!L.is_overdue;
                        followupTextEl.textContent = f;
                        followupEl.style.display = f ? 'inline' : 'none';
                        overdueTagEl.style.display = f && isOverdue ? 'inline' : 'none';
                        followupEl.classList.toggle('text-red-600', isOverdue);
                        followupEl.classList.toggle('font-medium', isOverdue);
                        followupEl.classList.toggle('text-gray-600', !isOverdue);
                    }
                    const notesEl = document.getElementById('currentLeadNotes');
                    if (notesEl) {
                        notesEl.textContent = L.last_call_notes ? ('{{ __("Last notes") }}: ' + L.last_call_notes) : '';
                        notesEl.style.display = L.last_call_notes ? 'block' : 'none';
                    }
                    document.querySelectorAll('.queue-item').forEach(el => {
                        el.classList.remove('bg-emerald-50', 'border-l-4', 'border-l-emerald-500');
                        el.querySelector('.w-8')?.classList.remove('bg-emerald-600', 'text-white');
                        el.querySelector('.w-8')?.classList.add('bg-gray-200', 'text-gray-700');
                    });
                    const nextItem = document.querySelector('.queue-item[data-lead-id="' + L.id + '"]');
                    if (nextItem) {
                        nextItem.classList.add('bg-emerald-50', 'border-l-4', 'border-l-emerald-500');
                        nextItem.querySelector('.w-8')?.classList.add('bg-emerald-600', 'text-white');
                        nextItem.querySelector('.w-8')?.classList.remove('bg-gray-200', 'text-gray-700');
                    }
                } else {
                    document.getElementById('currentLeadCard').innerHTML = '<div class="p-12 text-center"><div class="text-4xl mb-4">🎉</div><h3 class="text-lg font-semibold text-gray-800">' + (data.message || '{{ __("All caught up!") }}') + '</h3><a href="{{ route("students.my-leads") }}" class="inline-flex mt-4 px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-700">{{ __("View my leads") }}</a></div>';
                }
            })
            .catch(err => console.error(err));
        }

        window.afterCallLogSuccess = function() {
            const card = document.getElementById('currentLeadCard');
            if (card) {
                card.innerHTML = '<div class="p-12 text-center"><div class="text-4xl text-emerald-500 mb-2">✓</div><h4 class="font-semibold text-gray-800">{{ __("Call logged!") }}</h4><p class="text-sm text-gray-500">{{ __("Loading next lead...") }}</p></div>';
            }
            setTimeout(function() { location.reload(); }, 1200);
        };
    </script>
</x-app-layout>
