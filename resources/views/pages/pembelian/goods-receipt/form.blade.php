@extends('layouts.app')

@section('title', $title)

@section('content')
    @php($editing = $receipt !== null)

    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">{{ $title }}</h1>
            <p class="os-page-subtitle">
                {{ $editing ? 'Hanya tanggal, catatan, dan lokasi rak yang dapat diubah.' : 'Terima PO → buat batch → catat ledger masuk.' }}
            </p>
        </div>
        <a href="{{ $editing ? route('pembelian.penerimaan.show', $receipt) : route('pembelian.penerimaan') }}"
           class="os-btn os-btn--secondary">
            <x-icon name="arrow-left-line" /> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="os-alert os-alert--danger os-section-tight">{{ $errors->first() }}</div>
    @endif

    @unless ($editing)
        <form method="GET" action="{{ route('pembelian.penerimaan.create') }}" class="os-section os-card os-card-body">
            <div class="os-form-grid">
                <div class="os-field">
                    <label class="os-label" for="po">Purchase Order</label>
                    <select id="po" name="po" class="os-select" onchange="this.form.submit()">
                        <option value="">— pilih PO —</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->id }}" @selected($selected?->id === $order->id)>
                                {{ $order->no_po }} · {{ $order->supplier?->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    @endunless

    @if ($selected)
        <div class="os-section os-card os-card-body">
            <div class="os-card-head">
                <div>
                    <div class="os-card-title">{{ $selected->no_po }}</div>
                    <div class="os-card-subtitle">
                        {{ $selected->supplier?->nama }} · {{ $selected->sumberDana?->nama ?? '—' }}
                        · {{ $selected->tanggal_po?->format('d-m-Y') }}
                    </div>
                </div>
            </div>

            @if (count($items))
                <form method="POST"
                      action="{{ $editing ? route('pembelian.penerimaan.update', $receipt) : route('pembelian.penerimaan.store') }}">
                    @csrf
                    @if ($editing)
                        @method('PUT')
                    @endif
                    @unless ($editing)
                        <input type="hidden" name="po_id" value="{{ $selected->id }}">
                    @endunless

                    <div class="os-section-tight os-form-grid">
                        <div class="os-floating">
                            <input type="date" name="tanggal_terima" placeholder=" "
                                   value="{{ old('tanggal_terima', $receipt?->tanggal_terima?->toDateString() ?? now()->toDateString()) }}"
                                   class="os-floating-input">
                            <label class="os-floating-label">Tanggal terima</label>
                        </div>
                        <div class="os-floating">
                            <input type="text" name="catatan" placeholder=" "
                                   value="{{ old('catatan', $receipt?->catatan) }}" class="os-floating-input">
                            <label class="os-floating-label">Catatan</label>
                        </div>
                    </div>

                    <div class="os-table-wrap os-section-tight">
                        <table class="os-table">
                            <thead>
                            <tr>
                                <th>Obat</th>
                                @unless ($editing)
                                    <th class="os-td-right">Sisa PO</th>
                                @endunless
                                <th>No. batch</th>
                                <th>Expiry</th>
                                <th class="os-td-right">Jumlah</th>
                                <th class="os-td-right">Harga</th>
                                <th>Rak</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($items as $index => $item)
                                <tr>
                                    <td>{{ $item['nama'] }}</td>
                                    @unless ($editing)
                                        <td class="os-td-right">{{ $item['sisa'] }}</td>
                                    @endunless
                                    <td>
                                        @if ($editing)
                                            {{ $item['no_batch'] }}
                                        @else
                                            <input type="text" name="items[{{ $index }}][no_batch]"
                                                   value="{{ old('items.'.$index.'.no_batch', $item['no_batch']) }}" class="os-input">
                                            <input type="hidden" name="items[{{ $index }}][po_item_id]" value="{{ $item['po_item_id'] }}">
                                        @endif
                                    </td>
                                    <td>
                                        @if ($editing)
                                            {{ $item['tanggal_kedaluwarsa'] ? \Illuminate\Support\Carbon::parse($item['tanggal_kedaluwarsa'])->format('d-m-Y') : '—' }}
                                        @else
                                            <input type="date" name="items[{{ $index }}][tanggal_kedaluwarsa]"
                                                   value="{{ old('items.'.$index.'.tanggal_kedaluwarsa', $item['tanggal_kedaluwarsa']) }}" class="os-input">
                                        @endif
                                    </td>
                                    <td class="os-td-right">
                                        @if ($editing)
                                            {{ $item['jumlah'] }}
                                        @else
                                            <input type="number" min="1" max="{{ $item['sisa'] }}"
                                                   name="items[{{ $index }}][jumlah]"
                                                   value="{{ old('items.'.$index.'.jumlah', $item['jumlah']) }}" class="os-input">
                                        @endif
                                    </td>
                                    <td class="os-td-right">
                                        @if ($editing)
                                            Rp {{ number_format((float) $item['harga_beli'], 0, ',', '.') }}
                                        @else
                                            <input type="number" min="0" step="0.01"
                                                   name="items[{{ $index }}][harga_beli]"
                                                   value="{{ old('items.'.$index.'.harga_beli', $item['harga_beli']) }}" class="os-input">
                                        @endif
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][lokasi_rak]"
                                               value="{{ old('items.'.$index.'.lokasi_rak', $item['lokasi_rak']) }}" class="os-input">
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="os-section">
                        <button type="submit" class="os-btn os-btn--primary">
                            <x-icon name="save-line" /> {{ $editing ? 'Simpan Perubahan' : 'Simpan Penerimaan' }}
                        </button>
                    </div>
                </form>
            @else
                <div class="os-alert os-alert--warning os-section-tight">PO tidak memiliki sisa item yang dapat diterima.</div>
            @endif
        </div>
    @elseif (! $editing)
        <div class="os-section os-card os-card-body os-help">
            Pilih purchase order berstatus <em>dikirim</em> atau <em>diterima sebagian</em> untuk mulai menerima barang.
        </div>
    @endif
@endsection
