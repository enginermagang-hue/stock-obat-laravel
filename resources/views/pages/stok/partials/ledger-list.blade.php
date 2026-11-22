<div class="os-table-wrap">
    <table class="os-table os-table--hover">
        <thead>
            <tr>
                <th>Tanggal</th><th>Obat / batch</th><th>Tipe</th><th>Referensi</th>
                <th class="os-td-right">Sebelum</th><th class="os-td-right">Jumlah</th>
                <th class="os-td-right">Sesudah</th><th>User</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($entries as $entry)
            <tr>
                <td>{{ $entry->tanggal_transaksi?->format('d-m-Y H:i') }}</td>
                <td>
                    <div class="font-medium text-heading">{{ $entry->batch?->obat?->nama }}</div>
                    <div class="text-[13px] text-faint">{{ $entry->batch?->no_batch }}</div>
                </td>
                <td>
                    <span class="os-status-pill {{ $entry->tipe->value === 'masuk' ? 'os-status-pill--success' : 'os-status-pill--danger' }}">
                        {{ $entry->tipe->value }}
                    </span>
                </td>
                <td>{{ $entry->tipe_referensi->value }} #{{ $entry->referensi_id }}</td>
                <td class="os-td-right">{{ $entry->jumlah_sebelum }}</td>
                <td class="os-td-right">{{ $entry->tipe->value === 'masuk' ? '+' : '−' }}{{ $entry->jumlah }}</td>
                <td class="os-td-right">{{ $entry->jumlah_sesudah }}</td>
                <td>{{ $entry->user?->name ?: 'sistem' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="os-empty">Belum ada mutasi stok.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="os-pagination">{{ $entries->links() }}</div>
