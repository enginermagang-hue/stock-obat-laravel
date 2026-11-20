<div>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Stok Opname</h1>
            <p class="os-page-subtitle">Hitung fisik dengan snapshot stok sistem dan mode buta.</p>
        </div>
        <button wire:click="$toggle('showForm')" class="os-btn os-btn--primary">
            <x-icon name="add-line" :size="16" /> Buat Opname
        </button>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    @if ($showForm)
        <div class="os-section os-card os-card-body">
            <h2 class="os-card-title">Sesi opname baru</h2>
            <div class="os-section-tight os-form-grid">
                <div class="os-field">
                    <label class="os-label">Bulan</label>
                    <input type="number" min="1" max="12" wire:model="bulan" class="os-input">
                </div>
                <div class="os-field">
                    <label class="os-label">Tahun</label>
                    <input type="number" wire:model="tahun" class="os-input">
                </div>
                <div class="os-field">
                    <label class="os-label">Tipe</label>
                    <select wire:model="tipe" class="os-select">
                        <option value="berkala">Berkala</option>
                        <option value="mendadak">Mendadak</option>
                        <option value="lokal">Lokal</option>
                    </select>
                </div>
                <div class="os-field">
                    <label class="os-label">Mode</label>
                    <select wire:model="mode" class="os-select">
                        <option value="buta">Buta (disarankan)</option>
                        <option value="terbuka">Terbuka</option>
                    </select>
                </div>
            </div>
            <div class="os-floating os-section-tight">
                <input type="text" wire:model="catatan" placeholder=" " class="os-floating-input">
                <label class="os-floating-label">Catatan</label>
            </div>
            <div class="os-section">
                <button wire:click="generate" class="os-btn os-btn--primary">
                    <x-icon name="save-line" :size="16" /> Generate Opname
                </button>
            </div>
        </div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-table-wrap">
            <table class="os-table os-table--striped os-table--hover">
                <thead><tr><th>No. Opname</th><th>Periode</th><th>Tipe</th><th>Mode</th><th class="os-td-right">Item</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($opnames as $opname)
                    <tr>
                        <td>{{ $opname->no_opname }}</td>
                        <td>{{ $opname->periode_bulan }} / {{ $opname->periode_tahun }}</td>
                        <td>{{ $opname->tipe->value }}</td>
                        <td><span class="os-badge">{{ $opname->mode->value }}</span></td>
                        <td class="os-td-right">{{ $opname->items_count }}</td>
                        <td>
                            <span class="os-status-pill {{ $opname->status->value === 'selesai' ? 'os-status-pill--success' : ($opname->status->value === 'dibatalkan' ? 'os-status-pill--danger' : 'os-status-pill--process') }}">
                                {{ $opname->status->value }}
                            </span>
                        </td>
                        <td class="os-td-right">
                            <button wire:click="select({{ $opname->id }})" class="os-btn os-btn--sm os-btn--secondary">Buka</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="os-empty">Belum ada sesi opname.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="os-pagination">{{ $opnames->links() }}</div>
    </div>

    @if ($selected)
        @php
            $blindInput = $selected->mode->value === 'buta'
                && in_array($selected->status->value, ['draft', 'proses'], true);
        @endphp
        <div class="os-section os-card os-card--flush">
            <div class="os-card-head">
                <div>
                    <div class="os-card-title">{{ $selected->no_opname }}</div>
                    <div class="os-card-subtitle">Mode {{ $selected->mode->value }} · status {{ $selected->status->value }}</div>
                </div>
                <div class="os-toolbar">
                    @if (in_array($selected->status->value, ['draft', 'proses'], true))
                        <button wire:click="saveFisik" class="os-btn os-btn--primary">
                            <x-icon name="save-line" :size="16" /> Simpan Stok Fisik
                        </button>
                    @endif
                    @can('verify-opname')
                        @if ($selected->status->value === 'proses')
                            <button wire:click="verify({{ $selected->id }})" class="os-btn os-btn--secondary">
                                <x-icon name="check-line" :size="16" /> Verifikasi
                            </button>
                        @endif
                        @if (in_array($selected->status->value, ['diverifikasi', 'disetujui'], true))
                            <button wire:click="approve({{ $selected->id }})" class="os-btn os-btn--primary">
                                <x-icon name="check-double-line" :size="16" /> Approve & Adjustment
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
            <div class="os-table-wrap">
                <table class="os-table">
                    <thead>
                    <tr>
                        <th>Obat / batch</th>
                        @unless ($blindInput)
                            <th class="os-td-right">Stok sistem</th>
                        @endunless
                        <th class="os-td-right">Stok fisik</th>
                        @unless ($blindInput)
                            <th class="os-td-right">Selisih</th>
                            <th class="os-td-right">Nilai</th>
                        @endunless
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($selected->items as $item)
                        <tr>
                            <td>
                                <div class="font-medium text-heading">{{ $item->batch?->obat?->nama }}</div>
                                <div class="text-[13px] text-faint">{{ $item->batch?->no_batch }} · {{ $item->batch?->tanggal_kedaluwarsa?->format('d-m-Y') }}</div>
                            </td>
                            @unless ($blindInput)
                                <td class="os-td-right">{{ $item->stok_sistem }}</td>
                            @endunless
                            <td class="os-td-right">
                                @if (in_array($selected->status->value, ['draft', 'proses'], true))
                                    <input type="number" min="0" wire:model="fisik.{{ $item->id }}" class="os-input os-td-right" style="width:100px">
                                @else
                                    {{ $item->stok_fisik }}
                                @endif
                            </td>
                            @unless ($blindInput)
                                <td class="os-td-right {{ ($item->selisih ?? 0) !== 0 ? 'text-danger' : '' }}">{{ $item->selisih ?? '—' }}</td>
                                <td class="os-td-right">Rp {{ number_format((float) ($item->selisih ?? 0) * (float) $item->harga_beli, 0, ',', '.') }}</td>
                            @endunless
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
