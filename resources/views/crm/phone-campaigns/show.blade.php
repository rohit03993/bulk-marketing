<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('students.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Campaigns sent to') }} {{ $displayPhone }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (isset($student) && $student)
                @php
                    $labels = [
                        'lead' => __('Uncalled'),
                        'interested' => __('Interested'),
                        'not_interested' => __('Not Interested'),
                        'walkin_done' => __('Walk-in Done'),
                        'admission_done' => __('Admission Done'),
                        'follow_up_later' => __('Follow-up Later'),
                    ];
                    $currentLead = $student->lead_status ?? 'lead';
                @endphp
                <div class="bg-white rounded-lg shadow-sm p-4 sm:p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <div class="text-sm text-gray-500">{{ __('Student') }}</div>
                            <div class="text-lg font-semibold text-gray-900">
                                {{ $student->name }}
                                @if ($student->classSection)
                                    <span class="text-sm font-normal text-gray-500">— {{ $student->classSection->full_name }} ({{ $student->classSection->school->name ?? '—' }})</span>
                                @endif
                            </div>
                            <div class="mt-1 text-sm text-gray-600">
                                <span class="font-medium">{{ __('Lead status') }}:</span>
                                <span>{{ $labels[$currentLead] ?? ucfirst(str_replace('_', ' ', $currentLead)) }}</span>
                            </div>
                            @if ($student->tags->isNotEmpty())
                                <div class="mt-2 text-sm text-gray-600">
                                    <span class="font-medium">{{ __('Tags') }}:</span>
                                    @foreach ($student->tags as $tag)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 mr-1 mt-1">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="sm:text-right space-y-2">
                            <div>
                                <div class="text-xs text-gray-500 mb-1">{{ __('Update lead status') }}</div>
                                <form method="POST" action="{{ route('students.update-lead-status', $student) }}" class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                                    @csrf
                                    @method('PATCH')
                                    <select name="lead_status" class="mt-0 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @foreach ($labels as $value => $label)
                                            <option value="{{ $value }}" {{ $currentLead === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="inline-flex justify-center px-3 py-2 bg-gray-800 text-white text-xs font-medium rounded-md hover:bg-gray-700">
                                        {{ __('Save') }}
                                    </button>
                                </form>
                            </div>
                            <div class="flex flex-col gap-1 text-xs text-gray-500">
                                <button type="button"
                                        class="call-and-log-btn inline-flex justify-center px-3 py-2 bg-emerald-600 text-white text-xs font-medium rounded-md hover:bg-emerald-700"
                                        data-student-id="{{ $student->id }}"
                                        data-name="{{ e($student->name) }}"
                                        data-phone="{{ $displayPhone }}"
                                        data-tel="{{ preg_replace('/[^0-9]/', '', $student->whatsapp_phone_primary ?? $student->whatsapp_phone_secondary ?? '') }}">
                                    {{ __('Call & log') }}
                                </button>
                                <a href="{{ route('students.edit', $student) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Full student details') }}</a>
                            </div>
                        </div>
                    </div>

                    {{-- Call logging + history --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-100 pt-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-800 mb-2">{{ __('Recent calls') }}</h3>
                            @if ($calls->isEmpty())
                                <p class="text-xs text-gray-500">{{ __('No calls logged yet for this student.') }}</p>
                            @else
                                <ul class="divide-y divide-gray-100 text-xs">
                                    @foreach ($calls as $call)
                                        <li class="py-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <div>
                                                    <div class="font-medium text-gray-800">
                                                        {{ \App\Models\StudentCall::$callStatuses[$call->call_status] ?? ucfirst(str_replace('_',' ',$call->call_status)) }}
                                                    </div>
                                                    <div class="text-gray-500">
                                                        {{ $call->called_at?->format('M j, Y H:i') }}
                                                        @if ($call->user)
                                                            · {{ $call->user->name }}
                                                        @endif
                                                    </div>
                                                    @if ($call->status_changed_to)
                                                        <div class="text-gray-500">
                                                            {{ __('Lead status →') }}
                                                            <span class="font-medium">
                                                                {{ $labels[$call->status_changed_to] ?? ucfirst(str_replace('_',' ',$call->status_changed_to)) }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    @if ($call->next_followup_at)
                                                        <div class="text-gray-500">
                                                            {{ __('Next follow-up:') }}
                                                            <span class="{{ $call->next_followup_at->isPast() ? 'text-red-600 font-semibold' : '' }}">
                                                                {{ $call->next_followup_at->format('M j, Y H:i') }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @if ($call->call_notes)
                                                <div class="mt-1 text-gray-600">{{ \Illuminate\Support\Str::limit($call->call_notes, 160) }}</div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-sm font-medium text-gray-800 mb-2">{{ __('Send WhatsApp message') }}</h3>
                            <form method="POST" action="{{ route('phone.send-single', $phone) }}" class="space-y-2">
                                @csrf
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">{{ __('Template') }}</label>
                                    <select name="aisensy_template_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                                        <option value="">{{ __('Select template') }}</option>
                                        @foreach ($templates as $t)
                                            <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->param_count }} {{ Str::plural('param', $t->param_count) }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <p class="text-xs text-gray-500">
                                    {{ __('This will create a one-recipient campaign using the selected approved template and queue it to send to this number.') }}
                                </p>
                                <div>
                                    <button type="submit" class="inline-flex justify-center px-3 py-2 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-700">
                                        {{ __('Send WhatsApp') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

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
    @if (isset($student) && $student)
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
    @endif
</x-app-layout>
