@extends('layouts.app')

@section('title', 'Stok Opname')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Stok Opname</h1>
            <p class="os-page-subtitle">Hitung fisik dengan snapshot stok sistem dan mode buta.</p>
        </div>
        <button type="button" class="os-btn os-btn--primary" x-data @click="$dispatch('open-opname')">
            <x-icon name="add-line" /> Buat Opname
        </button>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    <div x-data="{ open: false }" @open-opname.window="open = true" x-show="open" x-cloak
         class="os-section os-card os-card-body">
        <h2 class="os-card-title">Sesi opname baru</h2>
        <form method="POST" action="{{ route('stok.opname.store') }}">
            @csrf
            <div class="os-section-tight os-form-grid">
                <div class="os-field">
                    <label class="os-label" for="bulan">Bulan</label>
                    <input id="bulan" type="number" name="bulan" min="1" max="12" value="{{ old('bulan', now()->month) }}" class="os-input" required>
                    @error('bulan') <span class="os-error">{{ $message }}</span> @enderror
                </div>
                <div class="os-field">
                    <label class="os-label" for="tahun">Tahun</label>
                    <input id="tahun" type="number" name="tahun" value="{{ old('tahun', now()->year) }}" class="os-input" required>
                    @error('tahun') <span class="os-error">{{ $message }}</span> @enderror
                </div>
                <div class="os-field">
                    <label class="os-label" for="tipe">Tipe</label>
                    <select id="tipe" name="tipe" class="os-select">
                        <option value="berkala" @selected(old('tipe') === 'berkala')>Berkala</option>
                        <option value="mendadak" @selected(old('tipe') === 'mendadak')>Mendadak</option>
                        <option value="lokal" @selected(old('tipe') === 'lokal')>Lokal</option>
                    </select>
                </div>
                <div class="os-field">
                    <label class="os-label" for="mode">Mode</label>
                    <select id="mode" name="mode" class="os-select">
                        <option value="buta" @selected(old('mode', 'buta') === 'buta')>Buta (disarankan)</option>
                        <option value="terbuka" @selected(old('mode') === 'terbuka')>Terbuka</option>
                    </select>
                </div>
            </div>
            <div class="os-floating os-section-tight">
                <input type="text" name="catatan" placeholder=" " value="{{ old('catatan') }}" class="os-floating-input">
                <label class="os-floating-label">Catatan</label>
            </div>
            <div class="os-section os-toolbar">
                <button type="submit" class="os-btn os-btn--primary">
                    <x-icon name="save-line" /> Generate Opname
                </button>
                <button type="button" class="os-btn os-btn--secondary" @click="open = false">
                    <x-icon name="close-line" /> Batal
                </button>
            </div>
        </form>
    </div>

    <div class="os-section os-card os-card--flush">
        <div class="os-table-wrap">
            <table class="os-table os-table--hover">
                <thead><tr><th>No. Opname</th><th>Periode</th><th>Tipe</th><th>Mode</th><th class="os-td-right">Item</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($opnames as $opname)
                    <tr>
                        <td>{{ $opname->no_opname }}</td>
                        <td>{{ $opname->periode_bulan }} / {{ $opname->periode_tahun }}</td>
                        <td>{{ $opname->tipe->value }}</td>
                        <td><span class="os-badge">{{ $opname->mode->value }}</span></td>
                        <td class="os-td-right">{{ $opname->items_count }}</td>
                        <td>
                            <span class="os-status-pill {{ $opname->status->value === 'selesai' ? 'os-status-pill--success' : ($opname->status->value === 'dibatalkan' ? 'os-status-pill--danger' : 'os-status-pill--process') }}">
                                {{ $opname->status->value }}
                            </span>
                        </td>
                        <td class="os-td-right">
                            <a href="{{ route('stok.opname', ['select' => $opname->id]) }}" class="os-btn os-btn--sm os-btn--secondary">Buka</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="os-empty">Belum ada sesi opname.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="os-pagination">{{ $opnames->links() }}</div>
    </div>

    @if ($selected)
        @php
            $blindInput = $selected->mode->value === 'buta'
                && in_array($selected->status->value, ['draft', 'proses'], true);
            $editable = in_array($selected->status->value, ['draft', 'proses'], true);
        @endphp
        <div class="os-section os-card os-card--flush">
            <div class="os-card-head">
                <div>
                    <div class="os-card-title">{{ $selected->no_opname }}</div>
                    <div class="os-card-subtitle">Mode {{ $selected->mode->value }} · status {{ $selected->status->value }}</div>
                </div>
                <div class="os-toolbar">
                    @can('verify-opname')
                        @if ($selected->status->value === 'proses')
                            <form method="POST" action="{{ route('stok.opname.verify', $selected) }}">
                                @csrf
                                <button type="submit" class="os-btn os-btn--secondary">
                                    <x-icon name="check-line" /> Verifikasi
                                </button>
                            </form>
                        @endif
                        @if (in_array($selected->status->value, ['diverifikasi', 'disetujui'], true))
                            <form method="POST" action="{{ route('stok.opname.approve', $selected) }}">
                                @csrf
                                <button type="submit" class="os-btn os-btn--primary">
                                    <x-icon name="check-double-line" /> Approve &amp; Adjustment
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
            <form method="POST" action="{{ route('stok.opname.fisik', $selected) }}">
                @csrf
                <div class="os-table-wrap">
                    <table class="os-table">
                        <thead>
                        <tr>
                            <th>Obat / batch</th>
                            @unless ($blindInput)
                                <th class="os-td-right">Stok sistem</th>
                            @endunless
                            <th class="os-td-right">Stok fisik</th>
                            @unless ($blindInput)
                                <th class="os-td-right">Selisih</th>
                                <th class="os-td-right">Nilai</th>
                            @endunless
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($selected->items as $item)
                            <tr>
                                <td>
                                    <div class="font-medium text-heading">{{ $item->batch?->obat?->nama }}</div>
                                    <div class="text-[13px] text-faint">{{ $item->batch?->no_batch }} · {{ $item->batch?->tanggal_kedaluwarsa?->format('d-m-Y') }}</div>
                                </td>
                                @unless ($blindInput)
                                    <td class="os-td-right">{{ $item->stok_sistem }}</td>
                                @endunless
                                <td class="os-td-right">
                                    @if ($editable)
                                        <input type="number" min="0" name="fisik[{{ $item->id }}]"
                                               value="{{ old('fisik.'.$item->id, $item->stok_fisik) }}"
                                               class="os-input os-td-right" style="width:100px">
                                    @else
                                        {{ $item->stok_fisik }}
                                    @endif
                                </td>
                                @unless ($blindInput)
                                    <td class="os-td-right {{ ($item->selisih ?? 0) !== 0 ? 'text-danger' : '' }}">{{ $item->selisih ?? '—' }}</td>
                                    <td class="os-td-right">Rp {{ number_format((float) ($item->selisih ?? 0) * (float) $item->harga_beli, 0, ',', '.') }}</td>
                                @endunless
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($editable)
                    <div class="os-section os-toolbar px-5 pb-4">
                        <button type="submit" class="os-btn os-btn--primary">
                            <x-icon name="save-line" /> Simpan Stok Fisik
                        </button>
                    </div>
                @endif
            </form>
        </div>
    @endif
@endsection
