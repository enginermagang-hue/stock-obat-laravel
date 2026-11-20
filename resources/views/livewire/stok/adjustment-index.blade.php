<div>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Penyesuaian Stok</h1>
            <p class="os-page-subtitle">Koreksi, expired, rusak, hilang, dan retur.</p>
        </div>
        <button wire:click="$toggle('showForm')" class="os-btn os-btn--primary">
            <x-icon name="add-line" :size="16" /> Buat Penyesuaian
        </button>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    @if ($showForm)
        <div class="os-section os-card os-card-body">
            <h2 class="os-card-title">Penyesuaian baru</h2>
            <div class="os-section-tight os-form-grid">
                <div class="os-field">
                    <label class="os-label">Tipe</label>
                    <select wire:model="tipe" class="os-select">
                        @foreach ($tipeOptions as $option)
                            <option value="{{ $option->value }}">{{ $option->value }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="os-floating">
                    <input type="date" wire:model="tanggal" placeholder=" " class="os-floating-input">
                    <label class="os-floating-label">Tanggal</label>
                </div>
            </div>

            <div class="os-table-wrap os-section-tight">
                <table class="os-table">
                    <thead><tr><th>Batch</th><th>Jumlah (±)</th><th>Alasan</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($items as $index => $item)
                        <tr>
                            <td>
                                <select wire:model="items.{{ $index }}.batch_id" class="os-select">
                                    <option value="">— pilih batch —</option>
                                    @foreach ($batches as $batch)
                                        <option value="{{ $batch->id }}">
                                            {{ $batch->obat?->nama }} · {{ $batch->no_batch }} · sisa {{ $batch->jumlah_sisa }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" wire:model="items.{{ $index }}.jumlah" class="os-input"></td>
                            <td><input type="text" wire:model="items.{{ $index }}.alasan" class="os-input"></td>
                            <td class="os-td-right">
                                <button wire:click="removeItem({{ $index }})" class="os-btn os-btn--sm os-btn--danger">
                                    <x-icon name="delete-bin-line" :size="14" />
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="os-floating os-section-tight">
                <input type="text" wire:model="catatan" placeholder=" " class="os-floating-input">
                <label class="os-floating-label">Catatan</label>
            </div>

            <div class="os-section os-toolbar">
                <button wire:click="addItem" class="os-btn os-btn--secondary">
                    <x-icon name="add-line" :size="16" /> Tambah item
                </button>
                <button wire:click="save" class="os-btn os-btn--primary">
                    <x-icon name="save-line" :size="16" /> Simpan
                </button>
            </div>
        </div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-table-wrap">
            <table class="os-table os-table--striped os-table--hover">
                <thead><tr><th>No.</th><th>Tanggal</th><th>Tipe</th><th class="os-td-right">Item</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($adjustments as $adjustment)
                    <tr>
                        <td>{{ $adjustment->no_penyesuaian }}</td>
                        <td>{{ $adjustment->tanggal?->format('d-m-Y') }}</td>
                        <td><span class="os-badge">{{ $adjustment->tipe->value }}</span></td>
                        <td class="os-td-right">{{ $adjustment->items_count }}</td>
                        <td>
                            <span class="os-status-pill {{ $adjustment->status->value === 'diproses' ? 'os-status-pill--success' : ($adjustment->status->value === 'dibatalkan' ? 'os-status-pill--danger' : 'os-status-pill--process') }}">
                                {{ $adjustment->status->value }}
                            </span>
                        </td>
                        <td class="os-td-right">
                            @can('approve-po')
                                @if ($adjustment->status === \App\Enums\StatusPenyesuaian::Draft)
                                    <div class="os-table-actions">
                                        <button wire:click="process({{ $adjustment->id }})" class="os-btn os-btn--sm os-btn--primary">Proses</button>
                                        <button wire:click="cancel({{ $adjustment->id }})" class="os-btn os-btn--sm os-btn--danger">Batal</button>
                                    </div>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="os-empty">Belum ada penyesuaian.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="os-pagination">{{ $adjustments->links() }}</div>
    </div>
</div>
