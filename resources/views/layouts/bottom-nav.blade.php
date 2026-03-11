{{-- Bottom navigation bar for telecallers (non-admin). Rendered only on mobile/tablet. --}}
@auth
@unless (Auth::user()->isAdmin())
<div x-data="{ moreOpen: false }" class="md:hidden">
    {{-- Spacer so page content doesn't hide behind fixed nav --}}
    <div class="h-16"></div>

    {{-- More slide-up panel --}}
    <div x-show="moreOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click.self="moreOpen = false"
         class="fixed inset-0 z-40 bg-black/40" style="display:none;">
        <div x-show="moreOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0" x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             class="absolute bottom-0 inset-x-0 bg-white rounded-t-2xl shadow-2xl pb-6 pt-3 px-4 max-h-[70vh] overflow-y-auto">
            <div class="w-10 h-1 rounded-full bg-slate-300 mx-auto mb-4"></div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 px-1">{{ __('More options') }}</p>
            <div class="space-y-1">
                <a href="{{ route('calls.report') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-100 transition">
                    <span class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                    {{ __('Call Report') }}
                </a>
                @if (Auth::user()->can_access_campaigns)
                <a href="{{ route('campaigns.index') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-100 transition">
                    <span class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </span>
                    {{ __('Campaigns') }}
                </a>
                @endif
                @if (Auth::user()->can_access_templates)
                <a href="{{ route('templates.index') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-100 transition">
                    <span class="w-9 h-9 rounded-lg bg-violet-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    </span>
                    {{ __('Templates') }}
                </a>
                @endif
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-100 transition">
                    <span class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </span>
                    {{ __('Profile') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 transition">
                        <span class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </span>
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Fixed bottom tab bar --}}
    <nav class="fixed bottom-0 inset-x-0 z-30 bg-white border-t border-slate-200 shadow-[0_-2px_10px_rgba(0,0,0,0.06)]">
        <div class="flex items-center justify-around h-16 max-w-lg mx-auto px-1">
            @php
                $tabs = [
                    ['route' => 'dashboard', 'match' => 'dashboard', 'label' => __('Home'), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
                    ['route' => 'students.my-leads', 'match' => 'students.my-leads', 'label' => __('My Leads'), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>'],
                    ['route' => 'students.call-queue', 'match' => 'students.call-queue*', 'label' => __('Call Queue'), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>'],
                    ['route' => 'students.followups', 'match' => 'students.followups', 'label' => __('Follow-ups'), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                    ['route' => 'calls.report', 'match' => 'calls.report', 'label' => __('Report'), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
                ];
            @endphp

            @foreach ($tabs as $tab)
                @php $active = request()->routeIs($tab['match']); @endphp
                <a href="{{ route($tab['route']) }}"
                   class="flex flex-col items-center justify-center flex-1 pt-1 pb-1 rounded-lg transition {{ $active ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                    <span class="{{ $active ? 'bg-indigo-100 shadow-sm' : '' }} rounded-xl p-1.5 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $tab['icon'] !!}</svg>
                    </span>
                    <span class="text-[10px] font-semibold mt-0.5 leading-tight">{{ $tab['label'] }}</span>
                </a>
            @endforeach

            {{-- More button --}}
            <button @click="moreOpen = !moreOpen"
                    class="flex flex-col items-center justify-center flex-1 pt-1 pb-1 rounded-lg transition text-slate-400 hover:text-slate-600"
                    :class="moreOpen && 'text-indigo-600'">
                <span class="rounded-xl p-1.5 transition" :class="moreOpen && 'bg-indigo-100 shadow-sm'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                </span>
                <span class="text-[10px] font-semibold mt-0.5 leading-tight">{{ __('More') }}</span>
            </button>
        </div>
    </nav>
</div>
@endunless
@endauth
