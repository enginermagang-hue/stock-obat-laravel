<div>
    <h1 class="os-page-title">Batch Obat</h1>
    <p class="os-page-subtitle">Stok per batch dengan urutan FEFO dan status kedaluwarsa.</p>

    <div class="os-section os-card os-card--flush">
        <div class="os-filter-bar">
            <div class="os-search-wrap">
                <x-icon name="search-line" :size="18" class="text-faint" />
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari obat / no. batch..." class="os-search">
            </div>
            <select wire:model.live="expiry" class="os-filter-select">
                <option value="">Semua expiry</option>
                <option value="expiring">Mendekati expired (90 hari)</option>
                <option value="expired">Sudah expired</option>
            </select>
        </div>
        <div class="os-table-wrap">
            <table class="os-table os-table--striped os-table--hover">
                <thead>
                    <tr>
                        <th>Obat</th><th>Batch</th><th>Expiry</th><th class="os-td-right">Sisa</th>
                        <th>Rak</th><th>Sumber dana</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($batches as $batch)
                    <tr>
                        <td>
                            <div class="font-medium text-heading">{{ $batch->obat?->nama }}</div>
                            <div class="text-[13px] text-faint">{{ $batch->obat?->kode_obat }}</div>
                        </td>
                        <td>{{ $batch->no_batch }}</td>
                        <td>{{ $batch->tanggal_kedaluwarsa?->format('d-m-Y') }}</td>
                        <td class="os-td-right">{{ $batch->jumlah_sisa }}</td>
                        <td>{{ $batch->lokasi_rak ?: '—' }}</td>
                        <td>{{ $batch->sumberDana?->nama ?: '—' }}</td>
                        <td>
                            @if ($batch->tanggal_kedaluwarsa?->isPast())
                                <span class="os-status-pill os-status-pill--danger">expired</span>
                            @elseif ($batch->tanggal_kedaluwarsa?->lte(now()->addDays(90)))
                                <span class="os-status-pill os-status-pill--warning">mendekati</span>
                            @elseif ($batch->jumlah_sisa <= ($batch->obat?->stok_minimum ?? 0))
                                <span class="os-status-pill os-status-pill--warning">menipis</span>
                            @else
                                <span class="os-status-pill os-status-pill--success">aktif</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="os-empty">Belum ada batch obat.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="os-pagination">{{ $batches->links() }}</div>
    </div>
</div>
