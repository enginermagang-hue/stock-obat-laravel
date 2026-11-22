@extends('layouts.app')

@section('title', $title)

@section('content')
    @php
        $status = $po->status;
        $editable = in_array($status, [\App\Enums\StatusPurchaseOrder::Draft, \App\Enums\StatusPurchaseOrder::Ditolak], true);
        $canReceive = in_array($status, [\App\Enums\StatusPurchaseOrder::Dikirim, \App\Enums\StatusPurchaseOrder::DiterimaSebagian], true);
        $statusClass = match ($status) {
            \App\Enums\StatusPurchaseOrder::Diterima => 'os-status-pill--success',
            \App\Enums\StatusPurchaseOrder::Dibatalkan, \App\Enums\StatusPurchaseOrder::Ditolak => 'os-status-pill--danger',
            default => 'os-status-pill--process',
        };
    @endphp

    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">{{ $po->no_po }}</h1>
            <p class="os-page-subtitle">{{ $po->supplier?->nama }} · {{ $po->tanggal_po?->format('d-m-Y') }}</p>
        </div>
        <a href="{{ route('pembelian.purchase-order') }}" class="os-btn os-btn--secondary">
            <x-icon name="arrow-left-line" /> Kembali
        </a>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    @error('status')
        <div class="os-alert os-alert--danger os-section-tight">{{ $message }}</div>
    @enderror

    @if (session('pagu_warning'))
        @php($warning = session('pagu_warning'))
        <div class="os-section os-alert os-alert--warning">
            <div class="os-card-title">Pagu sumber dana terlampaui</div>
            <div class="os-help os-section-tight">
                Pagu: Rp {{ number_format((float) ($warning['data']['pagu'] ?? 0), 0, ',', '.') }}
                · Komitmen bersih: Rp {{ number_format((float) ($warning['data']['komitmen_bersih'] ?? 0), 0, ',', '.') }}
                · Realisasi: Rp {{ number_format((float) ($warning['data']['realisasi'] ?? 0), 0, ',', '.') }}
                · Sisa: Rp {{ number_format((float) ($warning['data']['sisa'] ?? 0), 0, ',', '.') }}
            </div>
            <form method="POST" action="{{ route('pembelian.purchase-order.approve', $warning['po_id']) }}">
                @csrf
                <div class="os-field os-section-tight">
                    <label class="os-label" for="alasan_override_pagu">Alasan override pagu</label>
                    <input id="alasan_override_pagu" type="text" name="alasan_override_pagu" class="os-input"
                           placeholder="Contoh: kebutuhan outbreak, disetujui direktur">
                    @error('alasan_override_pagu') <div class="os-error">{{ $message }}</div> @enderror
                </div>
                <div class="os-toolbar os-section-tight">
                    <button type="submit" class="os-btn os-btn--primary">
                        <x-icon name="check-line" /> Setujui dengan override
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-card-head">
            <div>
                <div class="os-card-title">Informasi PO</div>
                <div class="os-card-subtitle">Status <span class="os-status-pill {{ $statusClass }}">{{ str_replace('_', ' ', $status->value) }}</span></div>
            </div>
            <div class="os-toolbar">
                @can('receive-goods')
                    @if ($editable)
                        <a href="{{ route('pembelian.purchase-order.edit', $po) }}" class="os-btn os-btn--sm os-btn--secondary">
                            <x-icon name="edit-line" /> Ubah
                        </a>
                    @endif
                @endcan
                @can('approve-po')
                    @if ($status === \App\Enums\StatusPurchaseOrder::Draft)
                        <form method="POST" action="{{ route('pembelian.purchase-order.approve', $po) }}">
                            @csrf
                            <button type="submit" class="os-btn os-btn--sm os-btn--primary">
                                <x-icon name="check-line" /> Approve
                            </button>
                        </form>
                        <form method="POST" action="{{ route('pembelian.purchase-order.reject', $po) }}"
                              onsubmit="return confirm('Tolak PO ini? PO dapat diperbaiki setelah ditolak.')">
                            @csrf
                            <button type="submit" class="os-btn os-btn--sm os-btn--danger">
                                <x-icon name="close-line" /> Tolak
                            </button>
                        </form>
                    @endif
                @endcan
                @if ($canReceive)
                    <a href="{{ route('pembelian.penerimaan', ['po' => $po->id]) }}" class="os-btn os-btn--sm os-btn--primary">
                        <x-icon name="archive-2-line" /> Terima
                    </a>
                @endif
                @can('approve-po')
                    @if (! in_array($status->value, ['diterima', 'dibatalkan'], true))
                        <form method="POST" action="{{ route('pembelian.purchase-order.cancel', $po) }}"
                              onsubmit="return confirm('Batalkan PO ini?')">
                            @csrf
                            <button type="submit" class="os-btn os-btn--sm os-btn--danger">Batalkan</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

        <div class="os-card-body os-form-grid">
            <div class="os-field">
                <span class="os-label">Supplier</span>
                <div>{{ $po->supplier?->nama ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Sumber dana</span>
                <div>{{ $po->sumberDana?->nama ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Tanggal PO</span>
                <div>{{ $po->tanggal_po?->format('d-m-Y') }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Total</span>
                <div>Rp {{ number_format((float) $po->total, 0, ',', '.') }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Dibuat oleh</span>
                <div>{{ $po->user?->name ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Disetujui oleh</span>
                <div>{{ $po->approver?->name ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Catatan</span>
                <div>{{ $po->catatan ?: '—' }}</div>
            </div>
            @if ($po->alasan_override_pagu)
                <div class="os-field">
                    <span class="os-label">Override pagu</span>
                    <div>{{ $po->alasan_override_pagu }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="os-section os-card os-card--flush">
        <div class="os-card-head">
            <div>
                <div class="os-card-title">Item PO</div>
                <div class="os-card-subtitle">{{ $po->items->count() }} item · total Rp {{ number_format((float) $po->total, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="os-table-wrap">
            <table class="os-table">
                <thead><tr><th>Obat</th><th class="os-td-right">Jumlah</th><th class="os-td-right">Diterima</th><th class="os-td-right">Harga beli</th><th class="os-td-right">Subtotal</th></tr></thead>
                <tbody>
                @foreach ($po->items as $item)
                    <tr>
                        <td>{{ $item->obat?->nama }}</td>
                        <td class="os-td-right">{{ $item->jumlah }}</td>
                        <td class="os-td-right">{{ $item->jumlah_diterima }}</td>
                        <td class="os-td-right">Rp {{ number_format((float) $item->harga_beli, 0, ',', '.') }}</td>
                        <td class="os-td-right">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($po->receipts->isNotEmpty())
        <div class="os-section os-card os-card--flush">
            <div class="os-card-head">
                <div>
                    <div class="os-card-title">Riwayat penerimaan</div>
                    <div class="os-card-subtitle">{{ $po->receipts->count() }} dokumen penerimaan</div>
                </div>
            </div>
            <div class="os-table-wrap">
                <table class="os-table">
                    <thead><tr><th>No. penerimaan</th><th>Tanggal</th><th class="os-td-right">Item</th><th class="os-td-right">Nilai</th></tr></thead>
                    <tbody>
                    @foreach ($po->receipts as $receipt)
                        <tr>
                            <td>{{ $receipt->no_penerimaan }}</td>
                            <td>{{ $receipt->tanggal_terima?->format('d-m-Y') }}</td>
                            <td class="os-td-right">{{ $receipt->items->count() }}</td>
                            <td class="os-td-right">Rp {{ number_format((float) $receipt->items->sum('subtotal'), 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
