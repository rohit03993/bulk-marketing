{{-- Log Call Result modal: Did the call connect? Who answered? Lead status? Notes & follow-up. --}}
@php
    $leadStatuses = [
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
        <div id="logCallModalHeader" class="px-4 py-3 bg-indigo-600 text-white flex justify-between items-center shrink-0">
            <h3 class="font-semibold" id="logCallModalTitle">📞 {{ __('Log Call Result') }}</h3>
            <button type="button" onclick="closeLogCallModal()" class="text-white/90 hover:text-white text-2xl leading-none">&times;</button>
        </div>
        <div class="p-4 overflow-y-auto flex-1">
            <div class="text-center mb-3 p-2 bg-gray-100 rounded-lg" id="logCallLeadInfo">
                <div class="font-semibold text-gray-900" id="logCallLeadName"></div>
                <div class="text-sm text-gray-500" id="logCallLeadPhone"></div>
            </div>
            <form id="quickCallForm">
                <input type="hidden" name="call_connected" id="callConnectedField" value="">
                <input type="hidden" name="call_direction" id="callDirectionField" value="outgoing">
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

                {{-- Follow-up (date + time, today allowed) --}}
                <div id="step5Followup" class="hidden mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">6. {{ __('Schedule follow-up') }} <span id="followupRequiredStar" class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-500 mb-3">{{ __('Quick pick or choose a custom date & time') }}</p>
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <button type="button" class="followup-quick px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs font-medium hover:border-indigo-300 hover:bg-indigo-50 transition" data-hours="2">{{ __('In 2 hrs') }}</button>
                        <button type="button" class="followup-quick px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs font-medium hover:border-indigo-300 hover:bg-indigo-50 transition" data-hours="4">{{ __('In 4 hrs') }}</button>
                        <button type="button" class="followup-quick px-2.5 py-1.5 rounded-lg border border-amber-200 bg-amber-50 text-xs font-medium hover:border-amber-300 hover:bg-amber-100 transition" data-time="18:00">{{ __('Today 6 PM') }}</button>
                        <button type="button" class="followup-quick px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs font-medium hover:border-indigo-300 hover:bg-indigo-50 transition" data-days="1">{{ __('Tomorrow') }}</button>
                        <button type="button" class="followup-quick px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs font-medium hover:border-indigo-300 hover:bg-indigo-50 transition" data-days="3">{{ __('3 days') }}</button>
                        <button type="button" class="followup-quick px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs font-medium hover:border-indigo-300 hover:bg-indigo-50 transition" data-days="7">{{ __('1 week') }}</button>
                        <button type="button" class="followup-quick px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs font-medium hover:border-indigo-300 hover:bg-indigo-50 transition" data-days="14">{{ __('2 weeks') }}</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="relative">
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">{{ __('Date') }}</label>
                            <input type="date" id="followupDateField" class="w-full rounded-xl border border-gray-200 py-2.5 pl-3 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">
                        </div>
                        <div class="relative">
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">{{ __('Time') }}</label>
                            <input type="time" id="followupTimeField" value="10:00" min="09:00" max="20:00" class="w-full rounded-xl border border-gray-200 py-2.5 pl-3 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">
                        </div>
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
    let logCallData = { leadId: null, connected: null, whoAnswered: null, leadStatus: null, callStatus: null, tags: [], requiresFollowup: true, notConnectedAttempts: 0 };

    function hide(el) {
        if (el) { el.classList.add('hidden'); el.style.display = 'none'; }
    }
    function show(el) {
        if (el) { el.classList.remove('hidden'); el.style.display = ''; }
    }

    function resetLogCallWizard() {
        logCallData = { leadId: null, connected: null, whoAnswered: null, leadStatus: null, callStatus: null, tags: [], requiresFollowup: true, notConnectedAttempts: 0, direction: 'outgoing' };
        var form = document.getElementById('quickCallForm');
        if (form) form.reset();
        var f = document.getElementById('callConnectedField'); if (f) f.value = '';
        f = document.getElementById('callDirectionField'); if (f) f.value = 'outgoing';
        f = document.getElementById('whoAnsweredField'); if (f) f.value = '';
        f = document.getElementById('leadStatusField'); if (f) f.value = '';
        f = document.getElementById('callStatusField'); if (f) f.value = '';
        var tf = document.getElementById('followupTimeField'); if (tf) tf.value = '10:00';
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
        var title = document.getElementById('logCallModalTitle');
        var header = document.getElementById('logCallModalHeader');
        if (title) title.textContent = '📞 {{ __("Log Call Result") }}';
        if (header) { header.classList.remove('bg-amber-600'); header.classList.add('bg-indigo-600'); }
    }

    window.openQuickCallModal = function(leadId, leadName, leadPhone, direction, notConnectedAttempts) {
        resetLogCallWizard();
        logCallData.leadId = leadId;
        logCallData.direction = direction || 'outgoing';
        logCallData.notConnectedAttempts = parseInt(notConnectedAttempts || 0, 10) || 0;
        var dirField = document.getElementById('callDirectionField');
        if (dirField) dirField.value = logCallData.direction;
        var nameEl = document.getElementById('logCallLeadName');
        var phoneEl = document.getElementById('logCallLeadPhone');
        if (nameEl) nameEl.textContent = leadName || '';
        if (phoneEl) phoneEl.textContent = leadPhone || '';

        if (logCallData.direction === 'incoming') {
            var title = document.getElementById('logCallModalTitle');
            var header = document.getElementById('logCallModalHeader');
            if (title) title.textContent = '📲 {{ __("Log Incoming Call") }}';
            if (header) { header.classList.remove('bg-indigo-600'); header.classList.add('bg-amber-600'); }
            setConnected(true);
            hide(document.getElementById('step1'));
        }

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
            logCallData.callStatus = null;
            const callStatusField = document.getElementById('callStatusField');
            if (callStatusField) callStatusField.value = '';
            logCallData.requiresFollowup = !['not_interested','admission_done'].includes(logCallData.leadStatus);
            if (notesStar) notesStar.style.display = 'inline';
            if (notesHint) notesHint.textContent = '{{ __("Minimum 10 characters required") }}';
        } else {
            hide(step2Conn);
            show(step2Not);
            hide(stepTags);
            if (logCallData.callStatus) {
                const projectedAttempts = (logCallData.notConnectedAttempts || 0) + 1;
                logCallData.requiresFollowup = (logCallData.callStatus !== 'wrong_number') && (projectedAttempts < 3);
            } else {
                logCallData.requiresFollowup = true;
            }
            if (notesStar) notesStar.style.display = 'none';
            if (notesHint) notesHint.textContent = '{{ __("Optional for not connected") }}';
        }
        show(stepDur);
        show(stepNotes);
        if (logCallData.requiresFollowup) {
            show(stepFup);
        } else {
            hide(stepFup);
        }
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
            const stepFup = document.getElementById('step5Followup');
            if (logCallData.requiresFollowup) show(stepFup); else hide(stepFup);
            suggestFollowup();
        });
    });
    document.querySelectorAll('.reason-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const v = this.dataset.value;
            logCallData.callStatus = v;
            document.getElementById('callStatusField').value = v;
            const projectedAttempts = (logCallData.notConnectedAttempts || 0) + 1;
            logCallData.requiresFollowup = (v !== 'wrong_number') && (projectedAttempts < 3);
            document.querySelectorAll('.reason-btn').forEach(b => { b.classList.remove('bg-indigo-600','text-white'); b.classList.add('border-gray-300'); });
            this.classList.add('bg-indigo-600','text-white'); this.classList.remove('border-gray-300');
            const stepFup = document.getElementById('step5Followup');
            if (logCallData.requiresFollowup) show(stepFup); else hide(stepFup);
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
    function getTodayDateString() {
        var d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    }
    function localDateString(d) {
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    }
    function localTimeString(d) {
        return String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
    }
    function setFollowupDateTime(dateStr, timeStr) {
        var dateInput = document.getElementById('followupDateField');
        var timeInput = document.getElementById('followupTimeField');
        if (dateInput) dateInput.value = dateStr;
        if (timeInput) timeInput.value = timeStr;
    }

    function clampTime(d) {
        if (d.getHours() < 9) { d.setHours(9, 0, 0); }
        if (d.getHours() >= 20) { d.setDate(d.getDate() + 1); d.setHours(9, 0, 0); }
        return d;
    }
    document.querySelectorAll('.followup-quick').forEach(btn => {
        btn.addEventListener('click', function() {
            var d = new Date();
            if (this.dataset.hours) {
                d.setHours(d.getHours() + parseInt(this.dataset.hours, 10));
                d = clampTime(d);
                setFollowupDateTime(localDateString(d), localTimeString(d));
            } else if (this.dataset.time) {
                setFollowupDateTime(getTodayDateString(), this.dataset.time);
            } else if (this.dataset.days) {
                d.setDate(d.getDate() + parseInt(this.dataset.days, 10));
                setFollowupDateTime(localDateString(d), '10:00');
            }
            document.querySelectorAll('.followup-quick').forEach(function(b) { b.classList.remove('bg-indigo-600','text-white','border-indigo-500'); b.classList.add('border-gray-200'); });
            this.classList.add('bg-indigo-600','text-white','border-indigo-500'); this.classList.remove('border-gray-200');
        });
    });

    function suggestFollowup() {
        var star = document.getElementById('followupRequiredStar');
        var dateInput = document.getElementById('followupDateField');
        var timeInput = document.getElementById('followupTimeField');
        var suggestionEl = document.getElementById('followupSuggestion');
        var today = getTodayDateString();
        if (logCallData.requiresFollowup) {
            star.style.display = 'inline';
            if (dateInput) {
                dateInput.setAttribute('min', today);
                if (!dateInput.value || dateInput.value < today) dateInput.value = today;
            }
            fetch('{{ route("students.call.suggest-followup") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                body: JSON.stringify({ lead_status: logCallData.leadStatus || 'lead', call_connected: logCallData.connected })
            }).then(r => r.json()).then(data => {
                if (data.suggested_datetime && dateInput) {
                    var datePart = (data.suggested_datetime + '').slice(0, 10);
                    if (datePart && datePart >= today) dateInput.value = datePart;
                    var timePart = (data.suggested_datetime + '').slice(11, 16);
                    if (timePart && timeInput) timeInput.value = timePart;
                }
                if (suggestionEl && data.suggested_label) suggestionEl.textContent = '💡 ' + (data.suggested_label || '');
            }).catch(function() {});
        } else {
            star.style.display = 'none';
            if (dateInput) { dateInput.value = ''; dateInput.removeAttribute('min'); }
            if (timeInput) timeInput.value = '10:00';
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
        var timeInput = document.getElementById('followupTimeField');
        var today = getTodayDateString();
        if (logCallData.requiresFollowup) {
            if (!dateInput || !dateInput.value) errors.push('{{ __("Schedule a follow-up") }}');
            else if (dateInput.value < today) errors.push('{{ __("Follow-up date cannot be in the past") }}');
            else {
                var picked = (timeInput && timeInput.value) ? timeInput.value : '10:00';
                var pickedMinutes = parseInt(picked.split(':')[0],10)*60 + parseInt(picked.split(':')[1],10);
                if (pickedMinutes < 540 || pickedMinutes > 1200) errors.push('{{ __("Follow-up time must be between 9 AM and 8 PM") }}');
                else if (dateInput.value === today) {
                    var now = new Date();
                    var nowMinutes = now.getHours()*60 + now.getMinutes();
                    if (pickedMinutes <= nowMinutes) errors.push('{{ __("Follow-up time must be later than now") }}');
                }
            }
        }
        if (!logCallData.leadId || logCallData.leadId === 'null' || logCallData.leadId === 'undefined') {
            errors.push('{{ __("Lead not found. Please close and start the call again from the queue.") }}');
        }
        if (errors.length) {
            err.textContent = errors.join('. ');
            return;
        }
        var hiddenDatetime = document.getElementById('followupDateTimeHidden');
        var timeVal = (timeInput && timeInput.value) ? timeInput.value : '10:00';
        if (hiddenDatetime && dateInput && dateInput.value) hiddenDatetime.value = dateInput.value + 'T' + timeVal + ':00';
        var form = this;
        var submitBtn = document.getElementById('quickCallSubmit');
        submitBtn.disabled = true;
        submitBtn.textContent = '{{ __("Saving...") }}';
        var formData = new FormData(form);
        formData.set('call_connected', logCallData.connected ? '1' : '0');
        formData.set('call_direction', logCallData.direction || 'outgoing');
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
                    openQuickCallModal(id, d.name || '', d.phone || '', 'outgoing', parseInt(d.notConnectedAttempts || 0, 10) || 0);
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
