@extends('layouts.app')

@section('title', 'RKO')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">RKO — Rencana Kebutuhan Obat</h1>
            <p class="os-page-subtitle">Metode historis dengan snapshot dan versi.</p>
        </div>
        <button type="button" class="os-btn os-btn--primary" x-data @click="$dispatch('open-rko')">
            <x-icon name="add-line" /> Susun RKO
        </button>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    <div x-data="{ open: false, metode: 'historis' }" @open-rko.window="open = true" x-show="open" x-cloak
         class="os-section os-card os-card-body">
        <h2 class="os-card-title">Susun RKO historis</h2>
        <form method="POST" action="{{ route('rko.build') }}">
            @csrf
            <div class="os-section-tight os-form-grid">
                <div class="os-field">
                    <label class="os-label" for="tahun">Tahun</label>
                    <input id="tahun" type="number" name="tahun" value="{{ old('tahun', now()->year) }}" class="os-input">
                </div>
                <div class="os-field">
                    <label class="os-label" for="periode">Periode (bulan)</label>
                    <select id="periode" name="periode" class="os-select">
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" @selected(old('periode', now()->month) == $i)>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="os-field">
                    <label class="os-label" for="lead_time_bulan">Lead time (bulan)</label>
                    <input id="lead_time_bulan" type="number" min="1" max="12" name="lead_time_bulan" value="{{ old('lead_time_bulan', 1) }}" class="os-input">
                </div>
                <div class="os-field">
                    <label class="os-label" for="faktor_pengaman">Faktor pengaman</label>
                    <input id="faktor_pengaman" type="number" step="0.1" min="0.5" max="5" name="faktor_pengaman" value="{{ old('faktor_pengaman', 1) }}" class="os-input">
                </div>
                <div class="os-field">
                    <label class="os-label" for="sumber_dana_id">Sumber dana default</label>
                    <select id="sumber_dana_id" name="sumber_dana_id" class="os-select">
                        <option value="">— pilih —</option>
                        @foreach ($sumberDana as $dana)
                            <option value="{{ $dana->id }}" @selected(old('sumber_dana_id') == $dana->id)>{{ $dana->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="os-field">
                    <label class="os-label" for="supplier_id">Supplier untuk PO</label>
                    <select id="supplier_id" name="supplier_id" class="os-select">
                        <option value="">— pilih —</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="os-field">
                    <label class="os-label" for="metode">Metode</label>
                    <select id="metode" name="metode" x-model="metode" class="os-select">
                        <option value="historis">Historis</option>
                        <option value="ai">AI (prediksi)</option>
                        <option value="kombinasi">Kombinasi</option>
                    </select>
                </div>
                <div class="os-field" x-show="metode === 'kombinasi'" x-cloak>
                    <label class="os-label" for="bobot_ai">Bobot AI (0–1)</label>
                    <input id="bobot_ai" type="number" step="0.1" min="0" max="1" name="bobot_ai" value="{{ old('bobot_ai', 0.5) }}" class="os-input">
                </div>
            </div>
            <div class="os-floating os-section-tight">
                <input type="text" name="catatan" placeholder=" " value="{{ old('catatan') }}" class="os-floating-input">
                <label class="os-floating-label">Catatan</label>
            </div>
            @if ($errors->any())
                <div class="os-alert os-alert--danger os-section-tight">{{ $errors->first() }}</div>
            @endif
            <div class="os-section os-toolbar">
                <button type="submit" class="os-btn os-btn--primary">
                    <x-icon name="calculator-line" /> Hitung RKO
                </button>
                <button type="button" class="os-btn os-btn--secondary" @click="open = false">
                    <x-icon name="close-line" /> Batal
                </button>
            </div>
            <div class="os-help os-section-tight">Rata-rata pemakaian diambil dari 6 periode LPLPO terverifikasi terakhir. Fallback ke stok minimum.</div>
        </form>
    </div>

    <div class="os-section os-card os-card--flush">
        <div class="os-table-wrap">
            <table class="os-table os-table--hover">
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
                        <td class="os-td-right">
                            <a href="{{ route('rko.index', ['select' => $rko->id]) }}" class="os-btn os-btn--sm os-btn--secondary">Buka</a>
                        </td>
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
                            <form method="POST" action="{{ route('rko.approve', $selected) }}">
                                @csrf
                                <button type="submit" class="os-btn os-btn--primary">
                                    <x-icon name="check-line" /> Approve RKO
                                </button>
                            </form>
                        @endif
                    @endcan
                    @can('approve-po')
                        @if ($selected->status === \App\Enums\StatusRko::Disetujui)
                            <form method="POST" action="{{ route('rko.purchase-order', $selected) }}">
                                @csrf
                                <button type="submit" class="os-btn os-btn--primary">
                                    <x-icon name="shopping-cart-2-line" /> Generate Draft PO
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
            <div class="os-table-wrap">
                <table class="os-table">
                    <thead>
                    <tr><th>Obat</th><th>Sumber</th><th class="os-td-right">Rata²</th><th class="os-td-right">Estimasi</th><th class="os-td-right">Stok akhir</th><th class="os-td-right">Dalam pesanan</th><th class="os-td-right">Pengaman</th><th class="os-td-right">Kebutuhan</th><th class="os-td-right">Total</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($selected->items as $item)
                        <tr>
                            <td>{{ $item->obat?->nama }}</td>
                            <td><span class="os-badge">{{ $item->sumber ?? 'historis' }}</span></td>
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
@endsection
