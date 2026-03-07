@props(['name', 'value' => '', 'id' => null, 'placeholder' => '9876543210'])
@php
    $id = $id ?? $name;
    $digitsOnly = preg_replace('/\D/', '', $value ?? '');
    $displayValue = strlen($digitsOnly) > 10 ? substr($digitsOnly, -10) : $digitsOnly;
@endphp
<div class="flex rounded-md shadow-sm">
    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-600 text-sm">+91</span>
    <input type="text"
           inputmode="numeric"
           pattern="[0-9]*"
           maxlength="10"
           name="{{ $name }}"
           id="{{ $id }}"
           value="{{ $displayValue }}"
           placeholder="{{ $placeholder }}"
           {{ $attributes->merge(['class' => 'block w-full rounded-r-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm']) }}
    />
</div>
