<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('campaigns.index') }}" class="text-gray-500 hover:text-gray-700">←</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New Campaign') }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                <form method="GET" action="{{ route('campaigns.create') }}" class="flex flex-wrap gap-4 items-end p-4 bg-gray-50 rounded-lg">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <label class="block text-xs font-medium text-gray-500">{{ __('School') }}</label>
                            <a href="{{ route('schools.create') }}" class="text-xs text-indigo-600 hover:text-indigo-800">{{ __('Add school') }}</a>
                        </div>
                        <select name="school_id" data-school-search="1" class="mt-1 rounded-md border-gray-300 text-sm" onchange="this.form.submit()">
                            <option value="">{{ __('Select') }}</option>
                            @foreach ($schools as $s)
                                <option value="{{ $s->id }}" {{ ($schoolId ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <label class="block text-xs font-medium text-gray-500">{{ __('Session') }}</label>
                            <a href="{{ route('sessions.create') }}" class="text-xs text-indigo-600 hover:text-indigo-800">{{ __('Add session') }}</a>
                        </div>
                        <select name="session_id" class="mt-1 rounded-md border-gray-300 text-sm" onchange="this.form.submit()">
                            <option value="">{{ __('Select') }}</option>
                            @foreach ($sessions as $s)
                                <option value="{{ $s->id }}" {{ ($sessionId ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-3 py-1.5 bg-gray-200 rounded-md text-sm">{{ __('Load classes') }}</button>
                </form>

                @if ($classSections->isNotEmpty())
                    <form method="POST" action="{{ route('campaigns.store') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <input type="hidden" name="school_id" value="{{ $schoolId }}">
                        <input type="hidden" name="academic_session_id" value="{{ $sessionId }}">
                        <div>
                            <x-input-label for="name" :value="__('Campaign name')" />
                            <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name')" required placeholder="e.g. Fee reminder April" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <x-input-label for="aisensy_template_id" :value="__('WhatsApp template (Aisensy)')" />
                                <a href="{{ route('templates.create', ['return_to' => 'campaigns/create?' . http_build_query(array_filter(['school_id' => $schoolId, 'session_id' => $sessionId]))]) }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('Add template') }}</a>
                            </div>
                            <select id="aisensy_template_id" name="aisensy_template_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Select template') }}</option>
                                @foreach ($templates as $t)
                                    <option value="{{ $t->id }}" {{ old('aisensy_template_id', request('aisensy_template_id')) == $t->id ? 'selected' : '' }}>{{ $t->name }} ({{ $t->param_count }} params)</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('aisensy_template_id')" class="mt-1" />
                            <div id="template-preview-wrap" class="mt-3 hidden">
                                <p class="text-xs font-medium text-gray-500 uppercase mb-1">{{ __('Message preview') }}</p>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-800 whitespace-pre-wrap" id="template-preview"></div>
                                <p class="mt-1 text-xs text-gray-500">{{ __('Placeholders like [Student name] will be replaced with real data per recipient.') }}</p>
                            </div>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-4 space-y-3">
                            <p class="text-sm font-semibold text-slate-800">{{ __('Media attachment (optional)') }}</p>
                            <p class="text-xs text-slate-500">
                                {{ __('Use this only for media-compatible approved templates. Easiest: upload JPG/JPEG/PNG below. For video/document, use public URL.') }}
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <x-input-label for="media_type" :value="__('Media type')" />
                                    <select id="media_type" name="media_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">{{ __('No media') }}</option>
                                        <option value="image" @selected(old('media_type') === 'image')>{{ __('Image (upload)') }}</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('media_type')" class="mt-1" />
                                </div>
                            </div>
                            <div>
                                <x-input-label for="media_upload" :value="__('Upload image (JPG/JPEG/PNG, max 5MB)')" />
                                <input
                                    type="file"
                                    id="media_upload"
                                    name="media_upload"
                                    accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ __('If uploaded, it will be served from your server and attached to the WhatsApp template.') }}
                                </p>
                                <p id="media_upload_size_note" class="mt-1 text-xs"></p>
                                <x-input-error :messages="$errors->get('media_upload')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="media_filename" :value="__('Media filename (optional)')" />
                                <x-text-input id="media_filename" name="media_filename" class="block mt-1 w-full" :value="old('media_filename')" placeholder="brochure.pdf" />
                                <x-input-error :messages="$errors->get('media_filename')" class="mt-1" />
                            </div>
                        </div>
                        <div>
                            <x-input-label :value="__('Target classes / sections')" />
                            <div class="mt-2 space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-md p-3">
                                @foreach ($classSections as $cs)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="class_section_ids[]" value="{{ $cs->id }}"
                                               {{ in_array($cs->id, $classSectionIds ?? []) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm">{{ $cs->full_name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('class_section_ids')" class="mt-1" />
                        </div>
                        <div class="flex gap-3 pt-2">
                            <x-primary-button>{{ __('Create campaign') }}</x-primary-button>
                            <a href="{{ route('campaigns.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                @else
                    @if ($schoolId && $sessionId)
                        <p class="text-sm text-amber-700 bg-amber-50 p-3 rounded">{{ __('No classes found for this school and session. Add classes first.') }}</p>
                    @else
                        <p class="text-sm text-gray-500">{{ __('Select school and session above to load classes, then fill campaign details.') }}</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
    @if (isset($templatePreviews) && $templatePreviews !== [])
    <script>
        (function () {
            const previews = @json($templatePreviews);
            const sel = document.getElementById('aisensy_template_id');
            const wrap = document.getElementById('template-preview-wrap');
            const box = document.getElementById('template-preview');
            function updatePreview() {
                const id = sel.value ? parseInt(sel.value, 10) : 0;
                if (!id || !previews[id]) {
                    wrap.classList.add('hidden');
                    return;
                }
                const data = previews[id];
                let text = data.body || '';
                (data.samples || []).forEach(function (sample, i) {
                    text = text.replace(new RegExp('\\{\\{' + (i + 1) + '\\}\\}', 'g'), sample || '');
                });
                box.textContent = text || '—';
                wrap.classList.remove('hidden');
            }
            sel.addEventListener('change', updatePreview);
            updatePreview();
        })();
    </script>
    @endif

    <script>
        (function () {
            const input = document.getElementById('media_upload');
            const note = document.getElementById('media_upload_size_note');
            if (!input || !note) return;

            const MAX_BYTES = 5 * 1024 * 1024; // 5MB

            const update = function () {
                const file = input.files && input.files[0] ? input.files[0] : null;
                if (!file) {
                    note.textContent = '';
                    note.style.color = '';
                    return;
                }

                const mb = (file.size / 1024 / 1024).toFixed(2);
                if (file.size > MAX_BYTES) {
                    note.textContent = 'Selected file is too large: ' + mb + 'MB (max 5MB).';
                    note.style.color = '#dc2626';
                    input.setCustomValidity('File too large. Max 5MB.');
                } else {
                    note.textContent = 'Selected file size: ' + mb + 'MB (max 5MB).';
                    note.style.color = '#6b7280';
                    input.setCustomValidity('');
                }
            };

            input.addEventListener('change', update);
            update();
        })();
    </script>
</x-app-layout>
