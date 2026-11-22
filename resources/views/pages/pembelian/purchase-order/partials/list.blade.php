<div class="os-table-wrap">
    <table class="os-table os-table--hover">
        <thead><tr><th>No. PO</th><th>Supplier</th><th>Sumber dana</th><th>Tanggal</th><th class="os-td-right">Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($orders as $order)
            <tr>
                <td>{{ $order->no_po }}</td>
                <td>{{ $order->supplier?->nama }}</td>
                <td>{{ $order->sumberDana?->nama }}</td>
                <td>{{ $order->tanggal_po?->format('d-m-Y') }}</td>
                <td class="os-td-right">Rp {{ number_format((float) $order->total, 0, ',', '.') }}</td>
                <td>
                    <span class="os-status-pill {{ $order->status->value === 'diterima' ? 'os-status-pill--success' : (in_array($order->status->value, ['dibatalkan', 'ditolak'], true) ? 'os-status-pill--danger' : 'os-status-pill--process') }}">
                        {{ str_replace('_', ' ', $order->status->value) }}
                    </span>
                </td>
                <td class="os-td-right">
                    <div class="os-table-actions">
                        <a href="{{ route('pembelian.purchase-order.show', $order) }}" class="os-btn os-btn--sm os-btn--secondary">Lihat</a>
                        @can('approve-po')
                            @if ($order->status === \App\Enums\StatusPurchaseOrder::Draft)
                                <form method="POST" action="{{ route('pembelian.purchase-order.approve', $order) }}">
                                    @csrf
                                    <button type="submit" class="os-btn os-btn--sm os-btn--primary">Approve</button>
                                </form>
                            @endif
                        @endcan
                        @if (in_array($order->status->value, ['dikirim', 'diterima_sebagian']))
                            <a href="{{ route('pembelian.penerimaan', ['po' => $order->id]) }}" class="os-btn os-btn--sm os-btn--primary">Terima</a>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="os-empty">Belum ada PO.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="os-pagination">{{ $orders->links() }}</div>
