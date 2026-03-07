@props(['active'])

@php
$classes = ($active ?? false)
    ? 'block px-3 py-2 rounded-lg text-base font-medium text-white bg-slate-700/60'
    : 'block px-3 py-2 rounded-lg text-base font-medium text-slate-300 hover:text-white hover:bg-slate-700/40';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
