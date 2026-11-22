@props([
    'show',
    'close',
    'size' => null,
])

<div x-show="{{ $show }}" x-cloak
     x-transition:enter="os-modal-fade-in"
     x-transition:leave="os-modal-fade-out"
     class="os-modal-backdrop"
     @keydown.escape.window="{{ $close }}"
     @click.self="{{ $close }}">
    <div x-show="{{ $show }}" x-cloak
         class="os-modal-panel {{ $size === '2xl' ? 'os-modal-panel--2xl' : '' }}"
         x-transition:enter="os-modal-pop-in"
         x-transition:leave="os-modal-pop-out">
        <div class="os-modal-head flex items-start justify-between gap-4">
            <div>
                {{ $title ?? '' }}
            </div>
            <button type="button" @click="{{ $close }}" class="os-action-trigger" aria-label="Tutup">
                <x-icon name="close-line" />
            </button>
        </div>
        <div class="os-modal-body">
            {{ $slot }}
        </div>
        @isset($footer)
            <div class="os-modal-footer">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
