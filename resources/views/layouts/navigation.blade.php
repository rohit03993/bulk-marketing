<nav x-data="{ open: false }" class="bg-slate-800 border-b border-slate-700/50 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0">
                    <div class="w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-white hidden sm:inline">TaskBook</span>
                </a>

                <div class="hidden sm:flex sm:items-center sm:gap-1 sm:ml-8">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{{ __('Dashboard') }}</x-nav-link>
                    @if (Auth::user()->isAdmin() || Auth::user()->can_access_schools)
                        <x-nav-link :href="route('schools.index')" :active="request()->routeIs('schools.*')">{{ __('Schools') }}</x-nav-link>
                        <x-nav-link :href="route('students.index')" :active="request()->routeIs('students.index')">{{ __('Students') }}</x-nav-link>
                    @endif
                    @unless (Auth::user()->isAdmin())
                        {{-- Telecaller: My leads, Start Calling, Follow-ups (not shown to admins) --}}
                        <x-nav-link :href="route('students.my-leads')" :active="request()->routeIs('students.my-leads')">{{ __('My Leads') }}</x-nav-link>
                        <x-nav-link :href="route('students.call-queue')" :active="request()->routeIs('students.call-queue*')">{{ __('Start Calling') }}</x-nav-link>
                        <x-nav-link :href="route('students.followups')" :active="request()->routeIs('students.followups')">{{ __('Follow-ups') }}</x-nav-link>
                    @endunless
                    @if (Auth::user()->isAdmin() || Auth::user()->can_access_campaigns)
                        <x-nav-link :href="route('campaigns.index')" :active="request()->routeIs('campaigns.*')">{{ __('Campaigns') }}</x-nav-link>
                    @endif
                    @if (Auth::user()->isAdmin() || Auth::user()->can_access_templates)
                        <x-nav-link :href="route('templates.index')" :active="request()->routeIs('templates.*')">{{ __('Templates') }}</x-nav-link>
                    @endif
                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">{{ __('Admin') }}</x-nav-link>
                        <x-nav-link :href="route('admin.staff.index')" :active="request()->routeIs('admin.staff.*')">{{ __('Staff') }}</x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:gap-2">
                <x-dropdown align="right" width="48" contentClasses="py-1 bg-white rounded-xl shadow-xl border border-slate-200">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-200 hover:text-white hover:bg-slate-700/50 transition">
                            <span class="w-8 h-8 rounded-full bg-slate-600 flex items-center justify-center text-white text-sm font-semibold">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="flex items-center sm:hidden">
                <button @click="open = ! open" class="p-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-700">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !open, 'inline-flex': open}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': !open}" class="sm:hidden border-t border-slate-700/50">
        <div class="pt-3 pb-3 space-y-0.5 px-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{{ __('Dashboard') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('schools.index')" :active="request()->routeIs('schools.*')">{{ __('Schools') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('students.index')" :active="request()->routeIs('students.index')">{{ __('Students') }}</x-responsive-nav-link>
            @unless (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('students.my-leads')" :active="request()->routeIs('students.my-leads')">{{ __('My Leads') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('students.call-queue')" :active="request()->routeIs('students.call-queue*')">{{ __('Start Calling') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('students.followups')" :active="request()->routeIs('students.followups')">{{ __('Follow-ups') }}</x-responsive-nav-link>
            @endunless
            <x-responsive-nav-link :href="route('campaigns.index')" :active="request()->routeIs('campaigns.*')">{{ __('Campaigns') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('templates.index')" :active="request()->routeIs('templates.*')">{{ __('Templates') }}</x-responsive-nav-link>
            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">{{ __('Admin') }}</x-responsive-nav-link>
            @endif
        </div>
        <div class="pt-3 pb-3 border-t border-slate-700/50 px-4">
            <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
            <p class="text-xs text-slate-400">{{ Auth::user()->email }}</p>
            <div class="mt-2 flex gap-2">
                <a href="{{ route('profile.edit') }}" class="text-sm text-blue-400 hover:text-blue-300">Profile</a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-slate-400 hover:text-white">Log Out</button>
                </form>
            </div>
        </div>
    </div>
</nav>
