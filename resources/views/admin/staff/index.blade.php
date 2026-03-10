<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Staff') }}
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.staff.create') }}" class="inline-flex items-center px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700">
                    {{ __('Add staff') }}
                </a>
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-500 hover:text-blue-600 transition">← {{ __('Back to Admin') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800 mb-4">{{ session('success') }}</div>
            @endif

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 font-medium text-slate-700">
                    {{ __('Staff can log in and shoot campaigns. Campaigns will show who shot them and when.') }}
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('Name') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('Email') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('Access') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ __('Added by') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($staff as $user)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                        <a href="{{ route('admin.staff.show', $user) }}" class="hover:text-blue-600">
                                            {{ $user->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $user->email }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        <span class="inline-flex flex-wrap gap-1">
                                            @if ($user->can_access_campaigns)<span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-xs">{{ __('Campaigns') }}</span>@endif
                                            @if ($user->can_access_templates)<span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-xs">{{ __('Templates') }}</span>@endif
                                            @if ($user->can_access_students)<span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-xs">{{ __('Students') }}</span>@endif
                                            @if ($user->can_access_schools)<span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-xs">{{ __('Schools') }}</span>@endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        <div class="flex items-center justify-between gap-2">
                                            <span>{{ $user->createdBy?->name ?? '—' }}</span>
                                            <a href="{{ route('admin.staff.edit', $user) }}" class="text-xs text-blue-600 hover:text-blue-700 font-medium">{{ __('Edit') }}</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">
                                        {{ __('No staff yet.') }} <a href="{{ route('admin.staff.create') }}" class="text-blue-600 hover:underline">{{ __('Add staff') }}</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
