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

    @php
        $leadStatusLabels = [
            'lead' => __('Uncalled'),
            'interested' => __('Interested'),
            'not_interested' => __('Not Interested'),
            'walkin_done' => __('Walk-in Done'),
            'admission_done' => __('Admission Done'),
            'follow_up_later' => __('Follow-up Later'),
        ];
    @endphp

    <div class="py-4 sm:py-6">
        <div class="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <div class="mb-4 sm:mb-6 grid grid-cols-3 gap-2 sm:gap-3">
                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-center">
                    <div class="text-[11px] sm:text-xs text-slate-500">{{ __('Not connected') }}</div>
                    <div class="mt-0.5 text-lg sm:text-xl font-semibold text-slate-900">{{ ($notConnectedToday?->count() ?? 0) }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-center">
                    <div class="text-[11px] sm:text-xs text-slate-500">{{ __('Due / overdue') }}</div>
                    <div class="mt-0.5 text-lg sm:text-xl font-semibold text-rose-600">{{ $dueStudents->total() }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-center">
                    <div class="text-[11px] sm:text-xs text-slate-500">{{ __('Upcoming') }}</div>
                    <div class="mt-0.5 text-lg sm:text-xl font-semibold text-sky-600">{{ $upcomingStudents->count() }}</div>
                </div>
            </div>

            <div class="space-y-5 sm:space-y-6">
                {{-- NOT CONNECTED TODAY --}}
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-4 sm:px-5 py-3 sm:py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm sm:text-base font-semibold text-slate-900">{{ __('Not connected today') }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('Quick retry list from today’s missed connects') }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                            {{ ($notConnectedToday?->count() ?? 0) }}
                        </span>
                    </div>

                    @if (empty($notConnectedToday) || $notConnectedToday->isEmpty())
                        <div class="p-8 text-center text-sm text-slate-500">
                            {{ __('No not-connected calls today.') }}
                        </div>
                    @else
                        <div class="p-3 sm:p-4 grid grid-cols-1 xl:grid-cols-2 gap-3">
                            @foreach ($notConnectedToday as $student)
                                @php
                                    $phones = array_filter([$student->whatsapp_phone_primary, $student->whatsapp_phone_secondary]);
                                    $statusLabel = \App\Models\StudentCall::$callStatuses[$student->last_call_status] ?? ucfirst(str_replace('_',' ', $student->last_call_status ?? ''));
                                @endphp
                                <article class="rounded-xl border border-rose-200 bg-rose-50/40 p-3 sm:p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-semibold text-slate-900 truncate">
                                                <a href="{{ route('students.show', $student) }}#calls" class="hover:text-indigo-700">
                                                    {{ $student->name }}
                                                </a>
                                            </h4>
                                            <p class="mt-1 text-xs text-slate-500 line-clamp-1">
                                                {{ $student->classSection?->school?->name ?? '—' }}
                                                @if ($student->classSection)
                                                    · {{ $student->classSection->full_name }}
                                                @endif
                                            </p>
                                        </div>
                                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-rose-100 text-rose-700">
                                            {{ $statusLabel }}
                                        </span>
                                    </div>

                                    <div class="mt-2 text-xs text-slate-600 space-y-1.5">
                                        <div>
                                            <span class="font-semibold">{{ __('Last call:') }}</span>
                                            {{ $student->last_call_at?->format('d M, h:i A') ?? '—' }}
                                        </div>
                                        @if ($student->last_call_notes)
                                            <div class="text-slate-600 bg-white rounded-lg border border-slate-200 p-2">
                                                {{ \Illuminate\Support\Str::limit($student->last_call_notes, 80) }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('students.show', $student) }}#messages"
                                               class="inline-flex items-center justify-center px-3 py-1.5 rounded-md border border-slate-300 text-xs font-medium text-slate-700 bg-white hover:bg-slate-50">
                                                {{ __('Open profile') }}
                                            </a>
                                        </div>
                                        @if (!empty($phones))
                                            @php $p = reset($phones); @endphp
                                            <a href="tel:{{ preg_replace('/\D+/', '', $p) }}"
                                               class="inline-flex items-center justify-center px-3 py-1.5 rounded-md bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                                                {{ __('Call again') }}
                                            </a>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>

                {{-- DUE / OVERDUE FOLLOW-UPS (today and earlier) --}}
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-4 sm:px-5 py-3 sm:py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm sm:text-base font-semibold text-slate-900">{{ __('Due now & overdue') }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('Immediate actions for today and past due leads') }}</p>
                        </div>
                    </div>

                    @if ($dueStudents->isEmpty())
                        <div class="p-8 text-center text-sm text-slate-500">
                            {{ __('No follow-ups are due right now.') }}
                        </div>
                    @else
                        <div class="p-3 sm:p-4 grid grid-cols-1 xl:grid-cols-2 gap-3">
                            @foreach ($dueStudents as $student)
                                @php
                                    $phones = array_filter([$student->whatsapp_phone_primary, $student->whatsapp_phone_secondary]);
                                    $lead = $student->lead_status ?? 'lead';
                                    $label = $leadStatusLabels[$lead] ?? ucfirst(str_replace('_',' ',$lead));
                                    $isOverdue = $student->next_followup_at && $student->next_followup_at->isPast();
                                @endphp
                                <article class="rounded-xl border {{ $isOverdue ? 'border-rose-200 bg-rose-50/40' : 'border-emerald-200 bg-emerald-50/40' }} p-3 sm:p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-semibold text-slate-900 truncate">
                                                <a href="{{ route('students.show', $student) }}" class="hover:text-indigo-700">
                                                    {{ $student->name }}
                                                </a>
                                            </h4>
                                            @if ($student->father_name)
                                                <p class="text-xs text-slate-500">{{ __('S/o') }} {{ $student->father_name }}</p>
                                            @endif
                                            <p class="mt-1 text-xs text-slate-500 line-clamp-1">
                                                {{ $student->classSection?->school?->name ?? '—' }}
                                                @if ($student->classSection)
                                                    · {{ $student->classSection->full_name }}
                                                @endif
                                            </p>
                                        </div>
                                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-100 text-amber-800">
                                            {{ $label }}
                                        </span>
                                    </div>

                                    <div class="mt-2 text-xs text-slate-600 space-y-1.5">
                                        <div>
                                            <span class="font-semibold">{{ __('Next follow-up:') }}</span>
                                            <span class="{{ $isOverdue ? 'text-rose-700 font-semibold' : '' }}">
                                                {{ $student->next_followup_at?->format('d M, h:i A') ?? '—' }}
                                            </span>
                                        </div>
                                        @if ($student->last_call_notes)
                                            <div class="text-slate-600 bg-white rounded-lg border border-slate-200 p-2">
                                                {{ \Illuminate\Support\Str::limit($student->last_call_notes, 80) }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <div class="flex flex-wrap gap-2">
                                            @if (!empty($phones))
                                                @php $p = reset($phones); $pClean = preg_replace('/[^0-9]/', '', $p); $pDisplay = $pClean ? '+91'.substr($pClean,-10) : $p; @endphp
                                                <button type="button"
                                                        class="call-and-log-btn inline-flex items-center justify-center px-3 py-1.5 rounded-md bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700"
                                                        data-student-id="{{ $student->id }}"
                                                        data-name="{{ e($student->name) }}"
                                                        data-phone="{{ $pDisplay }}"
                                                        data-tel="{{ $p }}">
                                                    {{ __('Call & log') }}
                                                </button>
                                                <a href="{{ route('phone.campaigns', reset($phones)) }}"
                                                   class="inline-flex items-center justify-center px-3 py-1.5 rounded-md border border-slate-300 text-xs font-medium text-slate-700 bg-white hover:bg-slate-50">
                                                    {{ __('Open history') }}
                                                </a>
                                            @endif
                                        </div>
                                        <a href="{{ route('students.edit', $student) }}"
                                           class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">
                                            {{ __('Details') }}
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <div class="px-3 sm:px-4 pb-3 sm:pb-4">{{ $dueStudents->links() }}</div>
                    @endif
                </section>

                {{-- UPCOMING FOLLOW-UPS --}}
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-4 sm:px-5 py-3 sm:py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm sm:text-base font-semibold text-slate-900">{{ __('Upcoming follow-ups (next :days days)', ['days' => $upcomingDays]) }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('Planned calls for the next window') }}</p>
                        </div>
                    </div>

                    @if ($upcomingStudents->isEmpty())
                        <div class="p-8 text-center text-sm text-slate-500">
                            {{ __('No upcoming follow-ups in the selected window.') }}
                        </div>
                    @else
                        <div class="p-3 sm:p-4 grid grid-cols-1 xl:grid-cols-2 gap-3">
                            @foreach ($upcomingStudents as $student)
                                @php
                                    $phones = array_filter([$student->whatsapp_phone_primary, $student->whatsapp_phone_secondary]);
                                    $lead = $student->lead_status ?? 'lead';
                                    $label = $leadStatusLabels[$lead] ?? ucfirst(str_replace('_',' ',$lead));
                                @endphp
                                <article class="rounded-xl border border-sky-200 bg-sky-50/40 p-3 sm:p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-semibold text-slate-900 truncate">
                                                <a href="{{ route('students.show', $student) }}" class="hover:text-indigo-700">
                                                    {{ $student->name }}
                                                </a>
                                            </h4>
                                            @if ($student->father_name)
                                                <p class="text-xs text-slate-500">{{ __('S/o') }} {{ $student->father_name }}</p>
                                            @endif
                                            <p class="mt-1 text-xs text-slate-500 line-clamp-1">
                                                {{ $student->classSection?->school?->name ?? '—' }}
                                                @if ($student->classSection)
                                                    · {{ $student->classSection->full_name }}
                                                @endif
                                            </p>
                                        </div>
                                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-100 text-amber-800">
                                            {{ $label }}
                                        </span>
                                    </div>

                                    <div class="mt-2 text-xs text-slate-600 space-y-1.5">
                                        <div>
                                            <span class="font-semibold">{{ __('Next follow-up:') }}</span>
                                            {{ $student->next_followup_at?->format('d M, h:i A') ?? '—' }}
                                        </div>
                                        @if ($student->last_call_notes)
                                            <div class="text-slate-600 bg-white rounded-lg border border-slate-200 p-2">
                                                {{ \Illuminate\Support\Str::limit($student->last_call_notes, 80) }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <div class="flex flex-wrap gap-2">
                                            @if (!empty($phones))
                                                @php $p = reset($phones); $pClean = preg_replace('/[^0-9]/', '', $p); $pDisplay = $pClean ? '+91'.substr($pClean,-10) : $p; @endphp
                                                <button type="button"
                                                        class="call-and-log-btn inline-flex items-center justify-center px-3 py-1.5 rounded-md bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700"
                                                        data-student-id="{{ $student->id }}"
                                                        data-name="{{ e($student->name) }}"
                                                        data-phone="{{ $pDisplay }}"
                                                        data-tel="{{ $p }}">
                                                    {{ __('Call & log') }}
                                                </button>
                                                <a href="{{ route('phone.campaigns', reset($phones)) }}"
                                                   class="inline-flex items-center justify-center px-3 py-1.5 rounded-md border border-slate-300 text-xs font-medium text-slate-700 bg-white hover:bg-slate-50">
                                                    {{ __('Open history') }}
                                                </a>
                                            @endif
                                        </div>
                                        <a href="{{ route('students.edit', $student) }}"
                                           class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">
                                            {{ __('Details') }}
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
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

