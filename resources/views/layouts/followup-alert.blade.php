{{-- Follow-up reminder bar for telecallers. Shows when there are overdue or due-today follow-ups. --}}
@auth
@unless (Auth::user()->isAdmin())
@php
    $userId = Auth::id();
    $now = now();
    $endOfToday = $now->copy()->endOfDay();

    $overdueCount = \App\Models\Student::where('assigned_to', $userId)
        ->whereNotNull('next_followup_at')
        ->where('next_followup_at', '<', $now)
        ->count();

    $dueTodayCount = \App\Models\Student::where('assigned_to', $userId)
        ->whereNotNull('next_followup_at')
        ->whereBetween('next_followup_at', [$now, $endOfToday])
        ->count();

    $totalDue = $overdueCount + $dueTodayCount;

    $dueNextHour = \App\Models\Student::where('assigned_to', $userId)
        ->whereNotNull('next_followup_at')
        ->whereBetween('next_followup_at', [$now, $now->copy()->addHour()])
        ->count();
@endphp

@if ($totalDue > 0)
<div class="bg-gradient-to-r {{ $overdueCount > 0 ? 'from-red-600 to-amber-500' : 'from-indigo-600 to-blue-500' }} text-white">
    <a href="{{ route('students.followups') }}" class="block max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-3 hover:opacity-90 transition">
        <div class="flex items-center gap-2 min-w-0">
            <span class="shrink-0 w-7 h-7 rounded-full bg-white/20 flex items-center justify-center text-sm">
                @if ($overdueCount > 0)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z"/></svg>
                @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @endif
            </span>
            <span class="text-sm font-medium truncate">
                @if ($overdueCount > 0 && $dueTodayCount > 0)
                    <strong>{{ $overdueCount }}</strong> {{ __('overdue') }}
                    <span class="mx-1 opacity-70">·</span>
                    <strong>{{ $dueTodayCount }}</strong> {{ __('due today') }}
                @elseif ($overdueCount > 0)
                    <strong>{{ $overdueCount }}</strong> {{ trans_choice('follow-up overdue|follow-ups overdue', $overdueCount) }}
                @else
                    <strong>{{ $dueTodayCount }}</strong> {{ trans_choice('follow-up due today|follow-ups due today', $dueTodayCount) }}
                @endif
                @if ($dueNextHour > 0)
                    <span class="ml-1 px-1.5 py-0.5 rounded bg-white/20 text-[10px] font-bold uppercase">{{ $dueNextHour }} {{ __('within 1 hr') }}</span>
                @endif
            </span>
        </div>
        <span class="shrink-0 text-xs font-semibold bg-white/20 px-3 py-1 rounded-full">
            {{ __('View') }} →
        </span>
    </a>
</div>
@endif
@endunless
@endauth
