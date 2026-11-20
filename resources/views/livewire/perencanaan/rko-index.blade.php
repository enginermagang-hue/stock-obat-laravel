<div>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">RKO — Rencana Kebutuhan Obat</h1>
            <p class="os-page-subtitle">Metode historis dengan snapshot dan versi.</p>
        </div>
        <button wire:click="$toggle('showForm')" class="os-btn os-btn--primary">
            <x-icon name="add-line" :size="16" /> Susun RKO
        </button>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    @if ($showForm)
        <div class="os-section os-card os-card-body">
            <h2 class="os-card-title">Susun RKO historis</h2>
            <div class="os-section-tight os-form-grid">
                <div class="os-field">
                    <label class="os-label">Tahun</label>
                    <input type="number" wire:model="tahun" class="os-input">
                </div>
                <div class="os-field">
                    <label class="os-label">Periode (bulan)</label>
                    <select wire:model="periode" class="os-select">
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="os-field">
                    <label class="os-label">Lead time (bulan)</label>
                    <input type="number" min="1" max="12" wire:model="lead_time_bulan" class="os-input">
                </div>
                <div class="os-field">
                    <label class="os-label">Faktor pengaman</label>
                    <input type="number" step="0.1" min="0.5" max="5" wire:model="faktor_pengaman" class="os-input">
                </div>
                <div class="os-field">
                    <label class="os-label">Sumber dana default</label>
                    <select wire:model="sumber_dana_id" class="os-select">
                        <option value="">— pilih —</option>
                        @foreach ($sumberDana as $s)
                            <option value="{{ $s->id }}">{{ $s->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="os-field">
                    <label class="os-label">Supplier untuk PO</label>
                    <select wire:model="supplier_id" class="os-select">
                        <option value="">— pilih —</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="os-floating os-section-tight">
                <input type="text" wire:model="catatan" placeholder=" " class="os-floating-input">
                <label class="os-floating-label">Catatan</label>
            </div>
            <div class="os-section">
                <button wire:click="build" class="os-btn os-btn--primary">
                    <x-icon name="calculator-line" :size="16" /> Hitung RKO
                </button>
            </div>
            <div class="os-help os-section-tight">Rata-rata pemakaian diambil dari 6 periode LPLPO terverifikasi terakhir. Fallback ke stok minimum.</div>
        </div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-table-wrap">
            <table class="os-table os-table--striped os-table--hover">
                <thead><tr><th>No. RKO</th><th>Tahun / periode</th><th>Tingkat</th><th>Metode</th><th>Versi</th><th class="os-td-right">Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($rkos as $rko)
                    <tr>
                        <td>{{ $rko->no_rko }}</td>
                        <td>{{ $rko->tahun }} / {{ $rko->periode }}</td>
                        <td><span class="os-badge">{{ $rko->tingkat->value }}</span></td>
                        <td><span class="os-badge">{{ $rko->metode->value }}</span></td>
                        <td>{{ $rko->versi }}</td>
                        <td class="os-td-right">Rp {{ number_format((float) $rko->total, 0, ',', '.') }}</td>
                        <td>
                            <span class="os-status-pill {{ $rko->status === \App\Enums\StatusRko::Disetujui ? 'os-status-pill--success' : ($rko->status === \App\Enums\StatusRko::Dibatalkan ? 'os-status-pill--danger' : 'os-status-pill--process') }}">
                                {{ $rko->status->value }}
                            </span>
                        </td>
                        <td class="os-td-right"><button wire:click="select({{ $rko->id }})" class="os-btn os-btn--sm os-btn--secondary">Buka</button></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="os-empty">Belum ada RKO.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="os-pagination">{{ $rkos->links() }}</div>
    </div>

    @if ($selected)
        <div class="os-section os-card os-card--flush">
            <div class="os-card-head">
                <div>
                    <div class="os-card-title">{{ $selected->no_rko }}</div>
                    <div class="os-card-subtitle">
                        {{ $selected->tahun }}/{{ $selected->periode }} · {{ $selected->tingkat->value }} · versi {{ $selected->versi }} · {{ $selected->status->value }}
                    </div>
                </div>
                <div class="os-toolbar">
                    @can('manage-rko')
                        @if ($selected->status === \App\Enums\StatusRko::Draft)
                            <button wire:click="approve({{ $selected->id }})" class="os-btn os-btn--primary">
                                <x-icon name="check-line" :size="16" /> Approve RKO
                            </button>
                        @endif
                    @endcan
                    @can('approve-po')
                        @if ($selected->status === \App\Enums\StatusRko::Disetujui)
                            <button wire:click="generatePo({{ $selected->id }})" class="os-btn os-btn--primary">
                                <x-icon name="shopping-cart-2-line" :size="16" /> Generate Draft PO
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
            <div class="os-table-wrap">
                <table class="os-table">
                    <thead>
                    <tr><th>Obat</th><th class="os-td-right">Rata²</th><th class="os-td-right">Estimasi</th><th class="os-td-right">Stok akhir</th><th class="os-td-right">Dalam pesanan</th><th class="os-td-right">Pengaman</th><th class="os-td-right">Kebutuhan</th><th class="os-td-right">Total</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($selected->items as $item)
                        <tr>
                            <td>{{ $item->obat?->nama }}</td>
                            <td class="os-td-right">{{ $item->pemakaian_rata_rata }}</td>
                            <td class="os-td-right">{{ $item->estimasi_pemakaian }}</td>
                            <td class="os-td-right">{{ $item->stok_akhir }}</td>
                            <td class="os-td-right">{{ $item->stok_dalam_pesanan }}</td>
                            <td class="os-td-right">{{ $item->stok_pengaman }}</td>
                            <td class="os-td-right">{{ $item->kebutuhan_bersih }}</td>
                            <td class="os-td-right">Rp {{ number_format((float) $item->total_estimasi, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
