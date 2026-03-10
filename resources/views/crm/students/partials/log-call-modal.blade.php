{{-- Log Call Result modal: Did the call connect? Who answered? Lead status? Notes & follow-up. --}}
@php
    $leadStatuses = [
        'lead' => __('Lead'),
        'interested' => __('Interested'),
        'not_interested' => __('Not Interested'),
        'walkin_done' => __('Walk-in Done'),
        'admission_done' => __('Admission Done'),
        'follow_up_later' => __('Follow-up Later'),
    ];
@endphp
<div id="logCallModalOverlay" class="fixed inset-0 z-30 hidden bg-black/40" style="display: none;"></div>
<div id="logCallModal" class="fixed inset-x-0 bottom-0 z-40 hidden sm:inset-0 sm:flex sm:items-center sm:justify-center" style="display: none;">
    <div class="bg-white rounded-t-2xl sm:rounded-2xl shadow-xl w-full sm:max-w-lg mx-auto max-h-[90vh] overflow-hidden flex flex-col">
        <div class="px-4 py-3 bg-indigo-600 text-white flex justify-between items-center shrink-0">
            <h3 class="font-semibold">📞 {{ __('Log Call Result') }}</h3>
            <button type="button" onclick="closeLogCallModal()" class="text-white/90 hover:text-white text-2xl leading-none">&times;</button>
        </div>
        <div class="p-4 overflow-y-auto flex-1">
            <div class="text-center mb-3 p-2 bg-gray-100 rounded-lg" id="logCallLeadInfo">
                <div class="font-semibold text-gray-900" id="logCallLeadName"></div>
                <div class="text-sm text-gray-500" id="logCallLeadPhone"></div>
            </div>
            <form id="quickCallForm">
                <input type="hidden" name="call_connected" id="callConnectedField" value="">
                <input type="hidden" name="who_answered" id="whoAnsweredField" value="">
                <input type="hidden" name="lead_status" id="leadStatusField" value="">
                <input type="hidden" name="call_status" id="callStatusField" value="">

                {{-- Step 1: Connected? --}}
                <div id="step1" class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">1. {{ __('Did the call connect?') }}</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" id="btnConnectedYes" class="connect-btn py-3 px-4 rounded-xl border-2 border-emerald-200 bg-emerald-50 text-emerald-800 font-medium hover:bg-emerald-100 transition">
                            <span class="block text-lg">✓</span>
                            <span class="font-semibold">{{ __('YES') }}</span>
                            <span class="block text-xs font-normal opacity-90">{{ __('Connected') }}</span>
                        </button>
                        <button type="button" id="btnConnectedNo" class="connect-btn py-3 px-4 rounded-xl border-2 border-gray-200 bg-gray-50 text-gray-700 font-medium hover:bg-gray-100 transition">
                            <span class="block text-lg">✗</span>
                            <span class="font-semibold">{{ __('NO') }}</span>
                            <span class="block text-xs font-normal opacity-90">{{ __('Not connected') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Step 2a: Who answered + Lead status (if connected) --}}
                <div id="step2Connected" class="hidden space-y-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">2. {{ __('Who answered?') }} <span class="text-red-500">*</span></label>
                        <div class="flex flex-wrap gap-2">
                            @foreach(\App\Models\StudentCall::$whoAnsweredOptions as $val => $label)
                                <button type="button" class="answer-btn px-3 py-2 rounded-lg border border-gray-300 text-sm hover:bg-gray-100 transition" data-value="{{ $val }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">3. {{ __('Lead status?') }} <span class="text-red-500">*</span></label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($leadStatuses as $val => $label)
                                <button type="button" class="status-btn px-3 py-2 rounded-lg border text-sm hover:bg-gray-100 transition {{ in_array($val, ['interested','admission_done']) ? 'border-emerald-300' : 'border-gray-300' }}" data-value="{{ $val }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Step 2b: Reason (if not connected) --}}
                <div id="step2NotConnected" class="hidden mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">2. {{ __("Why didn't it connect?") }} <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['no_answer'=>'No Answer','busy'=>'Busy','switched_off'=>'Switched Off','not_reachable'=>'Not Reachable','wrong_number'=>'Wrong Number','callback'=>'Callback'] as $val => $label)
                            <button type="button" class="reason-btn px-3 py-2 rounded-lg border border-gray-300 text-sm hover:bg-gray-100 transition" data-value="{{ $val }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Duration (both flows) --}}
                <div id="stepDuration" class="hidden mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('Call duration (minutes)') }}</label>
                    <input type="number" name="duration_minutes" id="durationMinutesField" min="0" max="120" value="" placeholder="0" class="w-24 rounded-lg border-gray-300 text-sm shadow-sm">
                </div>

                {{-- Tags (if connected) --}}
                <div id="step3Tags" class="hidden mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">4. {{ __('Conversation topics') }}</label>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach(\App\Models\StudentCall::$quickTags as $tag => $label)
                            <button type="button" class="tag-btn px-2.5 py-1.5 rounded-lg border border-gray-300 text-xs hover:bg-gray-100 transition" data-tag="{{ $tag }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Notes --}}
                <div id="step4Notes" class="hidden mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">5. {{ __('Call notes') }} <span id="notesRequiredStar" class="text-red-500">*</span></label>
                    <textarea name="call_notes" id="callNotesField" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-2 focus:ring-indigo-500" placeholder="{{ __('What was discussed? Key points, objections, next steps...') }}"></textarea>
                    <p class="text-xs text-gray-500 mt-1" id="notesHint">{{ __('Minimum 10 characters required') }}</p>
                </div>

                {{-- Follow-up (date only, from tomorrow onward) --}}
                <div id="step5Followup" class="hidden mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">6. {{ __('Schedule follow-up') }} <span id="followupRequiredStar" class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-500 mb-3">{{ __('Pick a date from tomorrow onward. Past and today are not allowed.') }}</p>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <button type="button" class="followup-quick px-3 py-2 rounded-xl border border-gray-200 text-sm font-medium hover:border-indigo-300 hover:bg-indigo-50 transition" data-days="1">{{ __('Tomorrow') }}</button>
                        <button type="button" class="followup-quick px-3 py-2 rounded-xl border border-gray-200 text-sm font-medium hover:border-indigo-300 hover:bg-indigo-50 transition" data-days="3">3 {{ __('days') }}</button>
                        <button type="button" class="followup-quick px-3 py-2 rounded-xl border border-gray-200 text-sm font-medium hover:border-indigo-300 hover:bg-indigo-50 transition" data-days="7">1 {{ __('week') }}</button>
                        <button type="button" class="followup-quick px-3 py-2 rounded-xl border border-gray-200 text-sm font-medium hover:border-indigo-300 hover:bg-indigo-50 transition" data-days="14">2 {{ __('weeks') }}</button>
                    </div>
                    <div class="relative">
                        <input type="date" id="followupDateField" class="w-full rounded-xl border border-gray-200 py-3 pl-4 pr-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition" placeholder="">
                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </span>
                    </div>
                    <input type="hidden" name="next_followup_at" id="followupDateTimeHidden" value="">
                    <p class="text-xs text-gray-500 mt-2" id="followupSuggestion"></p>
                </div>

                <div id="submitSection" class="hidden pt-3 border-t border-gray-200">
                    <div id="validationErrors" class="text-red-600 text-sm mb-2"></div>
                    <button type="submit" id="quickCallSubmit" class="w-full py-2.5 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700">
                        {{ __('Save call log') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    var PENDING_CALL_MAX_AGE_MS = 15 * 60 * 1000;
    let logCallData = { leadId: null, connected: null, whoAnswered: null, leadStatus: null, callStatus: null, tags: [], requiresFollowup: true };

    function hide(el) {
        if (el) { el.classList.add('hidden'); el.style.display = 'none'; }
    }
    function show(el) {
        if (el) { el.classList.remove('hidden'); el.style.display = ''; }
    }

    function resetLogCallWizard() {
        logCallData = { leadId: null, connected: null, whoAnswered: null, leadStatus: null, callStatus: null, tags: [], requiresFollowup: true };
        var form = document.getElementById('quickCallForm');
        if (form) form.reset();
        var f = document.getElementById('callConnectedField'); if (f) f.value = '';
        f = document.getElementById('whoAnsweredField'); if (f) f.value = '';
        f = document.getElementById('leadStatusField'); if (f) f.value = '';
        f = document.getElementById('callStatusField'); if (f) f.value = '';
        ['step2Connected','step2NotConnected','stepDuration','step3Tags','step4Notes','step5Followup','submitSection'].forEach(function(id) {
            hide(document.getElementById(id));
        });
        show(document.getElementById('step1'));
        document.querySelectorAll('.connect-btn').forEach(function(b) {
            b.classList.remove('bg-emerald-600','text-white','bg-red-600','border-indigo-600');
            b.classList.add('border-gray-200','bg-gray-50');
        });
        document.querySelectorAll('.answer-btn, .status-btn, .reason-btn, .tag-btn, .followup-quick').forEach(function(b) {
            b.classList.remove('bg-indigo-600','text-white');
            b.classList.add('border-gray-300');
        });
        var err = document.getElementById('validationErrors');
        if (err) err.textContent = '';
    }

    window.openQuickCallModal = function(leadId, leadName, leadPhone) {
        resetLogCallWizard();
        logCallData.leadId = leadId;
        var nameEl = document.getElementById('logCallLeadName');
        var phoneEl = document.getElementById('logCallLeadPhone');
        if (nameEl) nameEl.textContent = leadName || '';
        if (phoneEl) phoneEl.textContent = leadPhone || '';
        var overlay = document.getElementById('logCallModalOverlay');
        var modal = document.getElementById('logCallModal');
        if (overlay) { overlay.classList.remove('hidden'); overlay.style.display = 'block'; }
        if (modal) { modal.classList.remove('hidden'); modal.style.display = 'flex'; }
    };

    window.closeLogCallModal = function() {
        var overlay = document.getElementById('logCallModalOverlay');
        var modal = document.getElementById('logCallModal');
        if (overlay) { overlay.classList.add('hidden'); overlay.style.display = 'none'; }
        if (modal) { modal.classList.add('hidden'); modal.style.display = 'none'; }
    };

    function setConnected(connected) {
        logCallData.connected = connected;
        var cf = document.getElementById('callConnectedField');
        if (cf) cf.value = connected ? '1' : '0';

        var btnYes = document.getElementById('btnConnectedYes');
        var btnNo = document.getElementById('btnConnectedNo');
        if (btnYes) { btnYes.classList.remove('bg-emerald-600','text-white'); btnYes.classList.add('border-emerald-200','bg-emerald-50'); }
        if (btnNo) { btnNo.classList.remove('bg-red-600','text-white'); btnNo.classList.add('border-gray-200','bg-gray-50'); }
        if (connected && btnYes) { btnYes.classList.add('bg-emerald-600','text-white'); btnYes.classList.remove('border-emerald-200','bg-emerald-50'); }
        if (!connected && btnNo) { btnNo.classList.add('bg-red-600','text-white'); btnNo.classList.remove('border-gray-200','bg-gray-50'); }

        var step2Conn = document.getElementById('step2Connected');
        var step2Not = document.getElementById('step2NotConnected');
        var stepDur = document.getElementById('stepDuration');
        var stepTags = document.getElementById('step3Tags');
        var stepNotes = document.getElementById('step4Notes');
        var stepFup = document.getElementById('step5Followup');
        var submitSec = document.getElementById('submitSection');
        var notesStar = document.getElementById('notesRequiredStar');
        var notesHint = document.getElementById('notesHint');

        if (connected) {
            show(step2Conn);
            hide(step2Not);
            show(stepTags);
            if (notesStar) notesStar.style.display = 'inline';
            if (notesHint) notesHint.textContent = '{{ __("Minimum 10 characters required") }}';
        } else {
            hide(step2Conn);
            show(step2Not);
            hide(stepTags);
            if (notesStar) notesStar.style.display = 'none';
            if (notesHint) notesHint.textContent = '{{ __("Optional for not connected") }}';
        }
        show(stepDur);
        show(stepNotes);
        show(stepFup);
        show(submitSec);

        suggestFollowup();
        if (step2Conn && connected) {
            step2Conn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        if (step2Not && !connected) {
            step2Not.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    var btnYes = document.getElementById('btnConnectedYes');
    var btnNo = document.getElementById('btnConnectedNo');
    if (btnYes) btnYes.addEventListener('click', function() { setConnected(true); });
    if (btnNo) btnNo.addEventListener('click', function() { setConnected(false); });

    document.querySelectorAll('.answer-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const v = this.dataset.value;
            logCallData.whoAnswered = v;
            document.getElementById('whoAnsweredField').value = v;
            document.querySelectorAll('.answer-btn').forEach(b => { b.classList.remove('bg-indigo-600','text-white'); b.classList.add('border-gray-300'); });
            this.classList.add('bg-indigo-600','text-white'); this.classList.remove('border-gray-300');
            suggestFollowup();
        });
    });
    document.querySelectorAll('.status-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const v = this.dataset.value;
            logCallData.leadStatus = v;
            document.getElementById('leadStatusField').value = v;
            logCallData.requiresFollowup = !['not_interested','admission_done'].includes(v);
            document.querySelectorAll('.status-btn').forEach(b => { b.classList.remove('bg-indigo-600','text-white'); b.classList.add('border-gray-300'); });
            this.classList.add('bg-indigo-600','text-white'); this.classList.remove('border-gray-300');
            suggestFollowup();
        });
    });
    document.querySelectorAll('.reason-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const v = this.dataset.value;
            logCallData.callStatus = v;
            document.getElementById('callStatusField').value = v;
            logCallData.requiresFollowup = v !== 'wrong_number';
            document.querySelectorAll('.reason-btn').forEach(b => { b.classList.remove('bg-indigo-600','text-white'); b.classList.add('border-gray-300'); });
            this.classList.add('bg-indigo-600','text-white'); this.classList.remove('border-gray-300');
            suggestFollowup();
        });
    });
    document.querySelectorAll('.tag-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            this.classList.toggle('bg-indigo-600', !this.classList.contains('bg-indigo-600'));
            this.classList.toggle('text-white', this.classList.contains('bg-indigo-600'));
            this.classList.toggle('border-gray-300', !this.classList.contains('bg-indigo-600'));
        });
    });
    function getTomorrowDateString() {
        var d = new Date();
        d.setDate(d.getDate() + 1);
        return d.toISOString().slice(0, 10);
    }
    function setFollowupDateFromDays(days) {
        var d = new Date();
        d.setDate(d.getDate() + days);
        return d.toISOString().slice(0, 10);
    }

    document.querySelectorAll('.followup-quick').forEach(btn => {
        btn.addEventListener('click', function() {
            var days = parseInt(this.dataset.days, 10);
            var dateStr = setFollowupDateFromDays(days);
            var input = document.getElementById('followupDateField');
            if (input) input.value = dateStr;
            document.querySelectorAll('.followup-quick').forEach(function(b) { b.classList.remove('bg-indigo-600','text-white','border-indigo-500'); b.classList.add('border-gray-200'); });
            this.classList.add('bg-indigo-600','text-white','border-indigo-500'); this.classList.remove('border-gray-200');
        });
    });

    function suggestFollowup() {
        var star = document.getElementById('followupRequiredStar');
        var dateInput = document.getElementById('followupDateField');
        var suggestionEl = document.getElementById('followupSuggestion');
        var tomorrow = getTomorrowDateString();
        if (logCallData.requiresFollowup) {
            star.style.display = 'inline';
            if (dateInput) {
                dateInput.setAttribute('min', tomorrow);
                if (!dateInput.value || dateInput.value < tomorrow) dateInput.value = tomorrow;
            }
            fetch('{{ route("students.call.suggest-followup") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                body: JSON.stringify({ lead_status: logCallData.leadStatus || 'lead', call_connected: logCallData.connected })
            }).then(r => r.json()).then(data => {
                if (data.suggested_datetime && dateInput) {
                    var datePart = (data.suggested_datetime + '').slice(0, 10);
                    if (datePart && datePart >= tomorrow) dateInput.value = datePart;
                }
                if (suggestionEl && data.suggested_label) suggestionEl.textContent = '💡 ' + (data.suggested_label || '');
            }).catch(function() {});
        } else {
            star.style.display = 'none';
            if (dateInput) { dateInput.value = ''; dateInput.removeAttribute('min'); }
            if (suggestionEl) suggestionEl.textContent = '{{ __("No follow-up needed") }}';
        }
    }

    document.getElementById('quickCallForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const err = document.getElementById('validationErrors');
        err.textContent = '';
        const errors = [];
        if (logCallData.connected === null) errors.push('{{ __("Select if call was connected") }}');
        if (logCallData.connected === true) {
            if (!logCallData.whoAnswered) errors.push('{{ __("Select who answered") }}');
            if (!logCallData.leadStatus) errors.push('{{ __("Select lead status") }}');
            if ((document.getElementById('callNotesField').value || '').trim().length < 10) errors.push('{{ __("Call notes (min 10 characters)") }}');
        }
        if (logCallData.connected === false && !logCallData.callStatus) errors.push('{{ __("Select reason for not connecting") }}');
        var dateInput = document.getElementById('followupDateField');
        var tomorrow = getTomorrowDateString();
        if (logCallData.requiresFollowup) {
            if (!dateInput || !dateInput.value) errors.push('{{ __("Schedule a follow-up") }}');
            else if (dateInput.value < tomorrow) errors.push('{{ __("Follow-up date must be tomorrow or later") }}');
        }
        if (!logCallData.leadId || logCallData.leadId === 'null' || logCallData.leadId === 'undefined') {
            errors.push('{{ __("Lead not found. Please close and start the call again from the queue.") }}');
        }
        if (errors.length) {
            err.textContent = errors.join('. ');
            return;
        }
        var hiddenDatetime = document.getElementById('followupDateTimeHidden');
        if (hiddenDatetime && dateInput && dateInput.value) hiddenDatetime.value = dateInput.value + 'T10:00:00';
        var form = this;
        var submitBtn = document.getElementById('quickCallSubmit');
        submitBtn.disabled = true;
        submitBtn.textContent = '{{ __("Saving...") }}';
        var formData = new FormData(form);
        formData.set('call_connected', logCallData.connected ? '1' : '0');
        document.querySelectorAll('.tag-btn.bg-indigo-600').forEach(btn => {
            formData.append('tags[]', btn.dataset.tag);
        });
        var studentId = String(logCallData.leadId).replace(/\D/g, '') || logCallData.leadId;
        const url = '{{ url("students") }}/' + studentId + '/calls';
        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
            body: formData
        }).then(r => r.json().then(data => ({ ok: r.ok, data }))).then(({ ok, data }) => {
            if (ok && data.success) {
                closeLogCallModal();
                if (typeof window.afterCallLogSuccess === 'function') window.afterCallLogSuccess();
                else location.reload();
            } else {
                throw new Error(data.message || '{{ __("Error saving") }}');
            }
        }).catch(e => {
            err.textContent = e.message || '{{ __("Error saving") }}';
            submitBtn.disabled = false;
            submitBtn.textContent = '{{ __("Save call log") }}';
        });
    });

    document.getElementById('logCallModalOverlay').addEventListener('click', closeLogCallModal);

    (function() {
        function tryOpenPendingCallModal() {
            try {
                var raw = sessionStorage.getItem('pendingCallLog');
                if (!raw) return;
                var d = JSON.parse(raw);
                if (!d || typeof d.setAt !== 'number') { sessionStorage.removeItem('pendingCallLog'); return; }
                if (Date.now() - d.setAt > PENDING_CALL_MAX_AGE_MS) {
                    sessionStorage.removeItem('pendingCallLog');
                    return;
                }
                sessionStorage.removeItem('pendingCallLog');
                var id = parseInt(d.leadId, 10);
                if (!isNaN(id) && id > 0 && typeof openQuickCallModal === 'function') {
                    openQuickCallModal(id, d.name || '', d.phone || '');
                }
            } catch (e) {}
        }
        var hadHidden = false;
        var firstVisibleHandled = false;
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                hadHidden = true;
                return;
            }
            if (document.visibilityState !== 'visible') return;
            if (!firstVisibleHandled) {
                firstVisibleHandled = true;
                tryOpenPendingCallModal();
                return;
            }
            if (hadHidden) tryOpenPendingCallModal();
        });
    })();
})();
</script>
