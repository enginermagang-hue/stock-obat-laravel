<div class="os-table-wrap">
    <table class="os-table os-table--hover">
        <thead>
        <tr>
            <th>No. penerimaan</th>
            <th>PO</th>
            <th>Supplier</th>
            <th>Sumber dana</th>
            <th>Tanggal terima</th>
            <th class="os-td-right">Item</th>
            <th class="os-td-right">Nilai</th>
            <th>Penerima</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse ($receipts as $receipt)
            <tr>
                <td>{{ $receipt->no_penerimaan }}</td>
                <td>{{ $receipt->purchaseOrder?->no_po ?? '—' }}</td>
                <td>{{ $receipt->supplier?->nama ?? '—' }}</td>
                <td>{{ $receipt->sumberDana?->nama ?? '—' }}</td>
                <td>{{ $receipt->tanggal_terima?->format('d-m-Y') }}</td>
                <td class="os-td-right">{{ $receipt->items_count }}</td>
                <td class="os-td-right">Rp {{ number_format((float) $receipt->items()->sum('subtotal'), 0, ',', '.') }}</td>
                <td>{{ $receipt->user?->name ?? '—' }}</td>
                <td class="os-td-right">
                    <a href="{{ route('pembelian.penerimaan.show', $receipt) }}" class="os-btn os-btn--sm os-btn--secondary">Lihat</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="os-empty">Belum ada penerimaan barang.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="os-pagination">{{ $receipts->links() }}</div>
