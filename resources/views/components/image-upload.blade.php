@once
    @push('scripts')
        <script src="@asset('js/image-upload.js')" defer></script>
    @endpush
@endonce

<div {{ $attributes->merge(['class' => 'space-y-2']) }} data-image-upload>

    <div class="flex items-baseline justify-between gap-2">
        <label for="{{ $inputId }}" class="block text-sm font-medium">{{ $heading }}</label>
        <span class="text-xs text-slate-400">Max {{ $maxLabel() }}</span>
    </div>

    {{-- Click target and drop zone for the visually hidden input below. --}}
    <label for="{{ $inputId }}"
           data-image-upload-dropzone
           class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center transition hover:border-teal-500 hover:bg-teal-50/50">
        <svg class="h-6 w-6 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V6m0 0L8.25 9.75M12 6l3.75 3.75M3 16.5v1.875A2.625 2.625 0 0 0 5.625 21h12.75A2.625 2.625 0 0 0 21 18.375V16.5"/>
        </svg>
        <span class="text-sm text-slate-600">
            <span class="font-medium text-teal-700">Choose {{ $multiple ? 'images' : 'an image' }}</span>
            <span class="text-slate-400">or drag &amp; drop</span>
        </span>
        <span class="text-xs text-slate-400">{{ $hint() }}</span>
    </label>

    <input id="{{ $inputId }}"
           type="file"
           name="{{ $inputName }}"
           accept="{{ $accept }}"
           @if($multiple) multiple @endif
           class="sr-only"
           data-image-upload-input>

    {{-- Previews of files chosen in the browser, before anything is saved. --}}
    <div data-image-upload-pending class="flex flex-wrap gap-3 pt-1" hidden></div>

    {{-- Images already saved against this collection. --}}
    <div data-image-upload-saved
         @if($multiple) data-image-upload-sortable data-image-reorder-url="{{ route('admin.images.reorder') }}" @endif
         class="flex flex-wrap gap-3 pt-1"
         @if($images->isEmpty()) hidden @endif>
        @foreach($images as $image)
            <figure data-image-id="{{ $image->id }}"
                    @if($multiple) draggable="true" @endif
                    @class([
                        'js-deletable w-28 overflow-hidden rounded-lg border bg-white transition',
                        'border-slate-200' => ! ($multiple && $image->is_primary),
                        'border-teal-500 ring-2 ring-teal-200' => $multiple && $image->is_primary,
                    ])>
                <button type="button"
                        data-image-preview="{{ $image->url() }}"
                        data-image-caption="{{ $heading }}"
                        title="Preview full size"
                        class="block h-24 w-full cursor-zoom-in bg-slate-50">
                    <img src="{{ $image->url() }}"
                         alt="{{ $image->alt ?? $heading }}"
                         class="pointer-events-none h-full w-full object-cover">
                </button>

                <figcaption class="flex divide-x divide-slate-200 border-t border-slate-200 text-slate-500">
                    @if($multiple)
                        <button type="button"
                                data-image-primary="{{ route('admin.images.primary', $image) }}"
                                title="{{ $image->is_primary ? 'Primary image' : 'Make primary' }}"
                                aria-label="Make primary image"
                                aria-pressed="{{ $image->is_primary ? 'true' : 'false' }}"
                                @class([
                                    'flex flex-1 justify-center py-1.5 hover:bg-amber-50 hover:text-amber-500',
                                    'text-amber-500' => $image->is_primary,
                                ])>
                            <svg class="h-4 w-4" fill="{{ $image->is_primary ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.5a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/>
                            </svg>
                        </button>
                    @endif
                    <button type="button"
                            data-image-preview="{{ $image->url() }}"
                            data-image-caption="{{ $heading }}"
                            title="Preview"
                            aria-label="Preview image"
                            class="flex flex-1 justify-center py-1.5 hover:bg-slate-50 hover:text-teal-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                    </button>

                    <a href="{{ route('admin.images.download', $image) }}"
                       title="Download"
                       aria-label="Download image"
                       draggable="false"
                       class="flex flex-1 justify-center py-1.5 hover:bg-slate-50 hover:text-teal-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                    </a>

                    <button type="button"
                            data-delete="{{ route('admin.images.destroy', $image) }}"
                            data-confirm="Remove this image?"
                            title="Remove"
                            aria-label="Remove image"
                            class="flex flex-1 justify-center py-1.5 hover:bg-rose-50 hover:text-rose-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.2v.917m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                        </svg>
                    </button>
                </figcaption>
            </figure>
        @endforeach
    </div>

    {{-- Toggled by the observer in image-upload.js as saved images come and go. --}}
    <p data-image-upload-empty class="pt-1 text-xs text-slate-400" @if($images->isNotEmpty()) hidden @endif>
        No image uploaded yet.
    </p>
</div>
