<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? config('app.name') . ' - ' . $title : config('app.name') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts (run "npm run build" and deploy public/build if missing) -->
        @if (file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="font-sans antialiased" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;">
        <div class="min-h-screen bg-slate-50">
            @include('layouts.navigation')
            @include('layouts.followup-alert')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white border-b border-slate-200/80 shadow-sm">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="pb-12 bg-slate-100 min-h-[50vh]">
                {{ $slot }}
            </main>

            @include('layouts.bottom-nav')
        </div>
        <script>
            // Global school combobox helper (single-box search + dropdown suggestions).
            // Only applies where select has: data-school-search="1"
            (function () {
                const selects = document.querySelectorAll('select[name="school_id"][data-school-search="1"]');
                if (!selects.length) return;

                selects.forEach(function (selectEl, idx) {
                    if (!selectEl || selectEl.dataset.schoolSearchEnhanced === '1') return;
                    selectEl.dataset.schoolSearchEnhanced = '1';

                    const options = Array.from(selectEl.options).map(function (opt) {
                        return { value: String(opt.value ?? ''), text: String(opt.text ?? '') };
                    });
                    const selected = options.find(function (o) { return o.value === String(selectEl.value ?? ''); }) || { value: '', text: '' };

                    const wrapper = document.createElement('div');
                    wrapper.className = 'school-combobox';

                    const inputEl = document.createElement('input');
                    inputEl.type = 'text';
                    inputEl.placeholder = 'Search or select school';
                    inputEl.className = 'mt-1 block w-full rounded-md border-gray-300 text-sm';

                    const datalistId = 'school_search_list_' + idx + '_' + Math.floor(Math.random() * 100000);
                    inputEl.setAttribute('list', datalistId);

                    const listEl = document.createElement('datalist');
                    listEl.id = datalistId;

                    const hiddenEl = document.createElement('input');
                    hiddenEl.type = 'hidden';
                    hiddenEl.name = 'school_id';
                    hiddenEl.value = selected.value || '';

                    const hintEl = document.createElement('p');
                    hintEl.className = 'mt-1 text-[11px] text-slate-500';
                    hintEl.textContent = 'Type 3+ characters to filter schools';

                    options.forEach(function (opt) {
                        if (opt.value === '') return;
                        const o = document.createElement('option');
                        o.value = opt.text;
                        listEl.appendChild(o);
                    });

                    inputEl.value = selected.value === '' ? '' : (selected.text || '');

                    const findExact = function (text) {
                        const t = (text || '').trim().toLowerCase();
                        return options.find(function (o) {
                            return o.value !== '' && o.text.trim().toLowerCase() === t;
                        }) || null;
                    };

                    const findContains = function (text) {
                        const t = (text || '').trim().toLowerCase();
                        if (t.length < 3) return null;
                        return options.find(function (o) {
                            return o.value !== '' && o.text.toLowerCase().includes(t);
                        }) || null;
                    };

                    const syncValue = function () {
                        const raw = inputEl.value || '';
                        if (raw.trim() === '') {
                            hiddenEl.value = '';
                            hintEl.textContent = 'Type 3+ characters to filter schools';
                            return;
                        }
                        const exact = findExact(raw);
                        if (exact) {
                            hiddenEl.value = exact.value;
                            hintEl.textContent = 'School selected';
                            return;
                        }
                        hiddenEl.value = '';
                        hintEl.textContent = raw.trim().length >= 3
                            ? 'Select a school from suggestions'
                            : 'Type 3+ characters to filter schools';
                    };

                    inputEl.addEventListener('input', syncValue);
                    inputEl.addEventListener('change', syncValue);
                    inputEl.addEventListener('blur', function () {
                        if (hiddenEl.value) return;
                        const partial = findContains(inputEl.value || '');
                        if (partial) {
                            inputEl.value = partial.text;
                            hiddenEl.value = partial.value;
                            hintEl.textContent = 'School selected';
                        }
                    });

                    selectEl.parentNode.insertBefore(wrapper, selectEl);
                    wrapper.appendChild(inputEl);
                    wrapper.appendChild(listEl);
                    wrapper.appendChild(hiddenEl);
                    wrapper.appendChild(hintEl);
                    selectEl.remove();
                });
            })();
        </script>
    </body>
</html>
