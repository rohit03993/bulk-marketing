<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Follow-ups due') }}</h2>
            <a href="{{ route('students.my-leads') }}"
               class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                {{ __('My leads') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            {{-- NOT CONNECTED TODAY --}}
            <div class="mb-8">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-700">
                        {{ __('Not connected today') }}
                    </h3>
                    <span class="text-xs text-gray-500">
                        {{ ($notConnectedToday?->count() ?? 0) }}
                    </span>
                </div>

                @if (empty($notConnectedToday) || $notConnectedToday->isEmpty())
                    <div class="bg-white rounded-lg shadow-sm p-6 text-center text-gray-500">
                        {{ __('No not-connected calls today.') }}
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($notConnectedToday as $student)
                            @php
                                $phones = array_filter([$student->whatsapp_phone_primary, $student->whatsapp_phone_secondary]);
                                $statusLabel = \App\Models\StudentCall::$callStatuses[$student->last_call_status] ?? ucfirst(str_replace('_',' ', $student->last_call_status ?? ''));
                            @endphp
                            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col justify-between border-l-4 border-red-500">
                                <div>
                                    <div class="flex justify-between items-start gap-2">
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">
                                                <a href="{{ route('students.show', $student) }}#calls" class="hover:text-indigo-700">
                                                    {{ $student->name }}
                                                </a>
                                            </h3>
                                            <p class="mt-1 text-xs text-gray-500">
                                                {{ $student->classSection?->school?->name ?? '—' }}
                                                @if ($student->classSection)
                                                    · {{ $student->classSection->full_name }}
                                                @endif
                                            </p>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-red-100 text-red-800">
                                            {{ $statusLabel }}
                                        </span>
                                    </div>

                                    <div class="mt-2 text-xs text-gray-600 space-y-1">
                                        <div>
                                            <span class="font-medium">{{ __('Last call:') }}</span>
                                            {{ $student->last_call_at?->format('d M, h:i A') ?? '—' }}
                                        </div>
                                        @if ($student->last_call_notes)
                                            <div class="mt-1 text-gray-500 bg-gray-50 rounded p-2">
                                                {{ \Illuminate\Support\Str::limit($student->last_call_notes, 80) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-3 flex justify-between items-center">
                                    <div class="flex gap-2">
                                        @if (!empty($phones))
                                            @php $p = reset($phones); @endphp
                                            <a href="tel:{{ preg_replace('/\D+/', '', $p) }}"
                                               class="inline-flex items-center px-2.5 py-1.5 rounded-md bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                                                {{ __('Call again') }}
                                            </a>
                                        @endif
                                        <a href="{{ route('students.show', $student) }}#messages"
                                           class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-gray-300 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            {{ __('Open profile') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- DUE / OVERDUE FOLLOW-UPS (today and earlier) --}}
            <div class="mb-8">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    {{ __('Due now & overdue') }}
                </h3>

                @if ($dueStudents->isEmpty())
                    <div class="bg-white rounded-lg shadow-sm p-6 text-center text-gray-500">
                        {{ __('No follow-ups are due right now.') }}
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($dueStudents as $student)
                        @php
                            $phones = array_filter([$student->whatsapp_phone_primary, $student->whatsapp_phone_secondary]);
                            $lead = $student->lead_status ?? 'lead';
                            $label = [
                                'lead' => __('Uncalled'),
                                'interested' => __('Interested'),
                                'not_interested' => __('Not Interested'),
                                'walkin_done' => __('Walk-in Done'),
                                'admission_done' => __('Admission Done'),
                                'follow_up_later' => __('Follow-up Later'),
                            ][$lead] ?? ucfirst(str_replace('_',' ',$lead));
                        @endphp
                        <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col justify-between border-l-4 {{ $student->next_followup_at && $student->next_followup_at->isPast() ? 'border-red-500' : 'border-emerald-500' }}">
                            <div>
                                <div class="flex justify-between items-start gap-2">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900">
                                            <a href="{{ route('students.show', $student) }}" class="hover:text-indigo-700">
                                                {{ $student->name }}
                                            </a>
                                        </h3>
                                        @if ($student->father_name)
                                            <p class="text-xs text-gray-500">{{ __('S/o') }} {{ $student->father_name }}</p>
                                        @endif
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ $student->classSection?->school?->name ?? '—' }}
                                            @if ($student->classSection)
                                                · {{ $student->classSection->full_name }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-100 text-amber-800">
                                        {{ $label }}
                                    </span>
                                </div>
                                <div class="mt-2 text-xs text-gray-600 space-y-1">
                                    <div>
                                        <span class="font-medium">{{ __('Next follow-up:') }}</span>
                                        <span class="{{ $student->next_followup_at && $student->next_followup_at->isPast() ? 'text-red-600 font-semibold' : '' }}">
                                            {{ $student->next_followup_at?->format('d M, h:i A') ?? '—' }}
                                        </span>
                                    </div>
                                    @if ($student->last_call_notes)
                                        <div class="mt-1 text-gray-500 bg-gray-50 rounded p-2">
                                            {{ \Illuminate\Support\Str::limit($student->last_call_notes, 80) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3 flex justify-between items-center">
                                <div class="flex gap-2">
                                    @if (!empty($phones))
                                        @php $p = reset($phones); $pClean = preg_replace('/[^0-9]/', '', $p); $pDisplay = $pClean ? '+91'.substr($pClean,-10) : $p; @endphp
                                        <button type="button"
                                                class="call-and-log-btn inline-flex items-center px-2.5 py-1.5 rounded-md bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700"
                                                data-student-id="{{ $student->id }}"
                                                data-name="{{ e($student->name) }}"
                                                data-phone="{{ $pDisplay }}"
                                                data-tel="{{ $p }}">
                                            {{ __('Call & log') }}
                                        </button>
                                        <a href="{{ route('phone.campaigns', reset($phones)) }}"
                                           class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-gray-300 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            {{ __('Open history') }}
                                        </a>
                                    @endif
                                </div>
                                <a href="{{ route('students.edit', $student) }}"
                                   class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                    {{ __('Details') }}
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $dueStudents->links() }}</div>
                @endif
            </div>

            {{-- UPCOMING FOLLOW-UPS --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    {{ __('Upcoming follow-ups (next :days days)', ['days' => $upcomingDays]) }}
                </h3>

                @if ($upcomingStudents->isEmpty())
                    <div class="bg-white rounded-lg shadow-sm p-6 text-center text-gray-400 text-xs">
                        {{ __('No upcoming follow-ups in the selected window.') }}
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($upcomingStudents as $student)
                            @php
                                $phones = array_filter([$student->whatsapp_phone_primary, $student->whatsapp_phone_secondary]);
                                $lead = $student->lead_status ?? 'lead';
                                $label = [
                                    'lead' => __('Uncalled'),
                                    'interested' => __('Interested'),
                                    'not_interested' => __('Not Interested'),
                                    'walkin_done' => __('Walk-in Done'),
                                    'admission_done' => __('Admission Done'),
                                    'follow_up_later' => __('Follow-up Later'),
                                ][$lead] ?? ucfirst(str_replace('_',' ',$lead));
                            @endphp
                            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col justify-between border-l-4 border-sky-500">
                                <div>
                                    <div class="flex justify-between items-start gap-2">
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">
                                            <a href="{{ route('students.show', $student) }}" class="hover:text-indigo-700">
                                                {{ $student->name }}
                                            </a>
                                            </h3>
                                            @if ($student->father_name)
                                                <p class="text-xs text-gray-500">{{ __('S/o') }} {{ $student->father_name }}</p>
                                            @endif
                                            <p class="mt-1 text-xs text-gray-500">
                                                {{ $student->classSection?->school?->name ?? '—' }}
                                                @if ($student->classSection)
                                                    · {{ $student->classSection->full_name }}
                                                @endif
                                            </p>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-100 text-amber-800">
                                            {{ $label }}
                                        </span>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-600 space-y-1">
                                        <div>
                                            <span class="font-medium">{{ __('Next follow-up:') }}</span>
                                            <span>
                                                {{ $student->next_followup_at?->format('d M, h:i A') ?? '—' }}
                                            </span>
                                        </div>
                                        @if ($student->last_call_notes)
                                            <div class="mt-1 text-gray-500 bg-gray-50 rounded p-2">
                                                {{ \Illuminate\Support\Str::limit($student->last_call_notes, 80) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-3 flex justify-between items-center">
                                    <div class="flex gap-2">
                                        @if (!empty($phones))
                                            @php $p = reset($phones); $pClean = preg_replace('/[^0-9]/', '', $p); $pDisplay = $pClean ? '+91'.substr($pClean,-10) : $p; @endphp
                                            <button type="button"
                                                    class="call-and-log-btn inline-flex items-center px-2.5 py-1.5 rounded-md bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700"
                                                    data-student-id="{{ $student->id }}"
                                                    data-name="{{ e($student->name) }}"
                                                    data-phone="{{ $pDisplay }}"
                                                    data-tel="{{ $p }}">
                                                {{ __('Call & log') }}
                                            </button>
                                            <a href="{{ route('phone.campaigns', reset($phones)) }}"
                                               class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-gray-300 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                                                {{ __('Open history') }}
                                            </a>
                                        @endif
                                    </div>
                                    <a href="{{ route('students.edit', $student) }}"
                                       class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                        {{ __('Details') }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    @include('crm.students.partials.log-call-modal')
    <script>
        document.querySelectorAll('.call-and-log-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                try {
                    sessionStorage.setItem('pendingCallLog', JSON.stringify({
                        leadId: btn.dataset.studentId,
                        name: btn.dataset.name,
                        phone: btn.dataset.phone,
                        setAt: Date.now()
                    }));
                } catch (e) {}
                var tel = (this.dataset.tel || '').replace(/\D/g, '');
                if (tel) window.location.href = 'tel:' + (tel.length === 10 ? '+91' + tel : '+' + tel);
            });
        });
    </script>
</x-app-layout>

