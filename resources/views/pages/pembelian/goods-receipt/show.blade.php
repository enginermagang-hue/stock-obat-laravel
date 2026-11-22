@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">{{ $receipt->no_penerimaan }}</h1>
            <p class="os-page-subtitle">{{ $receipt->supplier?->nama }} · {{ $receipt->tanggal_terima?->format('d-m-Y') }}</p>
        </div>
        <div class="os-toolbar">
            <a href="{{ route('pembelian.penerimaan') }}" class="os-btn os-btn--secondary">
                <x-icon name="arrow-left-line" /> Kembali
            </a>
            <a href="{{ route('pembelian.penerimaan.edit', $receipt) }}" class="os-btn os-btn--secondary">
                <x-icon name="edit-line" /> Ubah
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-card-head">
            <div>
                <div class="os-card-title">Informasi penerimaan</div>
                <div class="os-card-subtitle">Dokumen penerimaan barang dan pencatatan stok</div>
            </div>
            @if ($receipt->purchaseOrder)
                <a href="{{ route('pembelian.purchase-order.show', $receipt->purchaseOrder) }}"
                   class="os-btn os-btn--sm os-btn--secondary">
                    <x-icon name="file-list-3-line" /> {{ $receipt->purchaseOrder->no_po }}
                </a>
            @endif
        </div>

        <div class="os-card-body os-form-grid">
            <div class="os-field">
                <span class="os-label">Purchase Order</span>
                <div>{{ $receipt->purchaseOrder?->no_po ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Supplier</span>
                <div>{{ $receipt->supplier?->nama ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Sumber dana</span>
                <div>{{ $receipt->sumberDana?->nama ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Tanggal terima</span>
                <div>{{ $receipt->tanggal_terima?->format('d-m-Y') }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Penerima</span>
                <div>{{ $receipt->user?->name ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Total nilai</span>
                <div>Rp {{ number_format((float) $receipt->items->sum('subtotal'), 0, ',', '.') }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Catatan</span>
                <div>{{ $receipt->catatan ?: '—' }}</div>
            </div>
        </div>
    </div>

    <div class="os-section os-card os-card--flush">
        <div class="os-card-head">
            <div>
                <div class="os-card-title">Item diterima</div>
                <div class="os-card-subtitle">{{ $receipt->items->count() }} item</div>
            </div>
        </div>
        <div class="os-table-wrap">
            <table class="os-table">
                <thead>
                <tr>
                    <th>Obat</th>
                    <th>No. batch</th>
                    <th>Expiry</th>
                    <th class="os-td-right">Jumlah</th>
                    <th class="os-td-right">Harga</th>
                    <th class="os-td-right">Subtotal</th>
                    <th>Rak</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($receipt->items as $item)
                    <tr>
                        <td>{{ $item->obat?->nama }}</td>
                        <td>{{ $item->no_batch }}</td>
                        <td>{{ $item->tanggal_kedaluwarsa?->format('d-m-Y') ?? '—' }}</td>
                        <td class="os-td-right">{{ $item->jumlah }}</td>
                        <td class="os-td-right">Rp {{ number_format((float) $item->harga_beli, 0, ',', '.') }}</td>
                        <td class="os-td-right">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                        <td>{{ $item->lokasi_rak ?: '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
