<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Admin') }} — {{ __('All data') }}
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.staff.index') }}" class="text-sm text-slate-700 hover:text-blue-600 font-medium transition">{{ __('Staff') }}</a>
                <a href="{{ route('admin.settings.postcall-whatsapp') }}" class="text-sm text-slate-700 hover:text-blue-600 font-medium transition">{{ __('Auto WhatsApp') }}</a>
                <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-blue-600 transition">← {{ __('Back to dashboard') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4">
                <a href="{{ route('schools.index') }}" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 hover:shadow-md hover:border-blue-200 transition">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Schools') }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">{{ $stats['schools'] }}</p>
                </a>
                <a href="{{ route('sessions.index') }}" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 hover:shadow-md hover:border-blue-200 transition">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Sessions') }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">{{ $stats['sessions'] }}</p>
                </a>
                <a href="{{ route('class-sections.index') }}" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 hover:shadow-md hover:border-blue-200 transition">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Classes') }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">{{ $stats['class_sections'] }}</p>
                </a>
                <a href="{{ route('students.index') }}" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 hover:shadow-md hover:border-blue-200 transition">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Students') }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">{{ $stats['students'] }}</p>
                </a>
                <a href="{{ route('student-imports.index') }}" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 hover:shadow-md hover:border-blue-200 transition">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Imports') }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">—</p>
                </a>
                <a href="{{ route('templates.index') }}" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 hover:shadow-md hover:border-blue-200 transition">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Templates') }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">{{ $stats['templates'] }}</p>
                </a>
                <a href="{{ route('campaigns.index') }}" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 hover:shadow-md hover:border-blue-200 transition">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('Campaigns') }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">{{ $stats['campaigns'] }}</p>
                </a>
            </div>
            <div class="text-center py-2">
                <p class="text-sm text-slate-600">{{ __('Messages sent (all time)') }}: <strong class="text-emerald-600">{{ $stats['messages_sent'] }}</strong></p>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 font-semibold text-slate-800">{{ __('Schools') }}</div>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($schools as $s)
                            <li class="px-5 py-2.5 flex justify-between items-center">
                                <span class="text-sm font-medium text-slate-800">{{ $s->name }}</span>
                                <a href="{{ route('schools.edit', $s) }}" class="text-xs text-blue-600 hover:text-blue-700 font-medium">{{ __('Edit') }}</a>
                            </li>
                        @empty
                            <li class="px-5 py-3 text-sm text-slate-500">{{ __('No schools') }}</li>
                        @endforelse
                    </ul>
                    <div class="px-5 py-3 border-t border-slate-100">
                        <a href="{{ route('schools.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">{{ __('View all') }}</a>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 font-semibold text-slate-800">{{ __('Recent campaigns') }}</div>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($recentCampaigns as $c)
                            <li class="px-5 py-2.5 flex justify-between items-center">
                                <span class="text-sm text-slate-800">{{ $c->name }}</span>
                                <a href="{{ route('campaigns.show', $c) }}" class="text-xs text-blue-600 hover:text-blue-700 font-medium">{{ $c->sent_count }}/{{ $c->total_recipients }}</a>
                            </li>
                        @empty
                            <li class="px-5 py-3 text-sm text-slate-500">{{ __('No campaigns') }}</li>
                        @endforelse
                    </ul>
                    <div class="px-5 py-3 border-t border-slate-100">
                        <a href="{{ route('campaigns.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">{{ __('View all') }}</a>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 font-semibold text-slate-800">{{ __('Recent imports') }}</div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($recentImports as $imp)
                        <li class="px-5 py-2.5 flex justify-between items-center text-sm">
                            <span class="text-slate-800">{{ $imp->original_filename }} — {{ $imp->school->name }}</span>
                            <span class="text-slate-500">{{ $imp->processed_rows }}/{{ $imp->total_rows }} · {{ $imp->status }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-3 text-slate-500">{{ __('No imports yet') }}</li>
                    @endforelse
                </ul>
                <div class="px-5 py-3 border-t border-slate-100">
                    <a href="{{ route('student-imports.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">{{ __('View all') }}</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
