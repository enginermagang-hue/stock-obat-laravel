<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <div class="os-search-field">
        <span aria-hidden="true">⌕</span>
        <input type="text" wire:model.live.debounce.250ms="query" placeholder="Search [CTRL + K]"
               @focus="open = true" @keydown.ctrl.k.prevent="$refs.input.focus()"
               x-ref="input" aria-label="Cari data master">
        <span class="os-search-shortcut">CTRL + K</span>
    </div>

    @if (strlen(trim($query)) >= 2)
        <div class="os-dropdown absolute left-0 right-0 top-12 z-40 max-h-80 overflow-y-auto p-2">
            @forelse ($results as $group => $items)
                <div class="px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-faint">{{ $group }}</div>
                @foreach ($items as $item)
                    <a class="os-dropdown-item" href="{{ route('master.'.($group === 'Puskesmas' ? 'facilities' : ($group === 'Sumber Dana' ? 'sumber-dana' : ($group === 'Supplier' ? 'supplier' : 'obat'))), ['search' => $group === 'Obat' ? $item->nama : $item->nama]) }}">
                        {{ $item->nama }}
                        <span class="text-faint">{{ $item->kode ?? $item->kode_obat }}</span>
                    </a>
                @endforeach
            @empty
                <div class="px-4 py-3 text-[13px] text-faint">Tidak ada hasil untuk “{{ $query }}”.</div>
            @endforelse
        </div>
    @endif
</div>
