@props(['active'])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-white bg-slate-700/60 border-b-0'
    : 'inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-700/40 transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
