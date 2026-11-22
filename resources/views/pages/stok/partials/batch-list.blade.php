<div class="os-table-wrap">
    <table class="os-table os-table--hover">
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
