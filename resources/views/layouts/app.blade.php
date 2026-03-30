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
            // Global school select search helper:
            // adds a 3+ character search input above every school_id dropdown.
            (function () {
                const selects = document.querySelectorAll('select[name="school_id"]');
                if (!selects.length) return;

                selects.forEach(function (selectEl) {
                    if (!selectEl || selectEl.dataset.schoolSearchEnhanced === '1') return;
                    selectEl.dataset.schoolSearchEnhanced = '1';

                    const options = Array.from(selectEl.options).map(function (opt) {
                        return { value: opt.value, text: opt.text };
                    });

                    const searchEl = document.createElement('input');
                    searchEl.type = 'text';
                    searchEl.placeholder = 'Type 3+ letters to search school';
                    searchEl.className = 'mt-1 mb-1 block w-full rounded-md border-gray-300 text-sm';
                    const hintEl = document.createElement('p');
                    hintEl.className = 'mt-1 text-[11px] text-slate-500';
                    hintEl.textContent = 'Type 3+ characters to filter schools';

                    const wrapper = document.createElement('div');
                    wrapper.className = 'school-search-enhancer';
                    selectEl.parentNode.insertBefore(wrapper, selectEl);
                    wrapper.appendChild(searchEl);
                    wrapper.appendChild(hintEl);
                    wrapper.appendChild(selectEl);

                    const render = function (q) {
                        const term = (q || '').trim().toLowerCase();
                        const useFilter = term.length >= 3;
                        const selectedValue = selectEl.value;
                        selectEl.innerHTML = '';

                        options.forEach(function (opt) {
                            if (!useFilter || opt.text.toLowerCase().includes(term) || opt.value === '') {
                                const o = document.createElement('option');
                                o.value = opt.value;
                                o.text = opt.text;
                                if (opt.value === selectedValue) o.selected = true;
                                selectEl.appendChild(o);
                            }
                        });
                    };

                    searchEl.addEventListener('input', function () {
                        render(searchEl.value);
                    });
                });
            })();
        </script>
    </body>
</html>
