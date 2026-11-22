@extends('layouts.app')

@section('title', 'Anggaran & Realisasi')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Anggaran &amp; Realisasi</h1>
            <p class="os-page-subtitle">Pagu tahunan per sumber dana dan unit, dengan komitmen PO dan realisasi penerimaan.</p>
        </div>
        <a href="{{ route('anggaran.index', ['tahun' => $tahun, 'form' => 1]) }}" class="os-btn os-btn--primary">
            <x-icon name="add-line" /> Tambah Pagu
        </a>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    <div class="os-section os-card os-card--flush"
         x-data="ajaxFilter({ endpoint: '{{ route('anggaran.index') }}' })">
        <form method="GET" x-ref="filterForm" class="os-filter-bar" @submit.prevent="submit()">
            <div class="os-field os-filter">
                <select name="tahun" class="os-filter-select" @change="load(1)">
                    @for ($year = now()->year + 1; $year >= now()->year - 3; $year--)
                        <option value="{{ $year }}" @selected($tahun === $year)>Tahun {{ $year }}</option>
                    @endfor
                </select>
            </div>
            <div class="os-field os-filter">
                <select name="facility" class="os-filter-select" @change="load(1)">
                    <option value="all" @selected($facilityFilter === 'all')>Semua unit</option>
                    <option value="0" @selected($facilityFilter === '0')>Rumah Sakit (RS)</option>
                    @foreach ($facilities as $facility)
                        <option value="{{ $facility->id }}" @selected($facilityFilter === (string) $facility->id)>{{ $facility->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="os-field os-filter">
                <select name="sumber_dana" class="os-filter-select" @change="load(1)">
                    <option value="">Semua sumber dana</option>
                    @foreach ($sumberDana as $sumber)
                        <option value="{{ $sumber->id }}" @selected($sumberDanaId === $sumber->id)>{{ $sumber->nama }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="os-btn os-btn--sm os-btn--secondary">Terapkan</button>
        </form>
    </div>

    @if (request()->boolean('form') || $editing)
        <div class="os-section os-card os-card-body">
            <h2 class="os-card-title">{{ $editing ? 'Revisi pagu' : 'Tambah pagu' }}</h2>
            <form method="POST" action="{{ route('anggaran.store') }}">
                @csrf
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                @if ($editing)
                    <input type="hidden" name="allow_update" value="1">
                @endif
                <div class="os-section-tight os-form-grid">
                    <div class="os-field">
                        <label class="os-label" for="sumber_dana_id">Sumber dana</label>
                        <select id="sumber_dana_id" name="sumber_dana_id" class="os-select" @disabled($editing)>
                            <option value="">— pilih —</option>
                            @foreach ($sumberDana as $sumber)
                                <option value="{{ $sumber->id }}" @selected(old('sumber_dana_id', $editing?->sumber_dana_id) == $sumber->id)>{{ $sumber->nama }}</option>
                            @endforeach
                        </select>
                        @error('sumber_dana_id') <div class="os-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="os-field">
                        <label class="os-label" for="facility_id">Unit</label>
                        <select id="facility_id" name="facility_id" class="os-select" @disabled($editing)>
                            <option value="0">Rumah Sakit (RS)</option>
                            @foreach ($facilities as $facility)
                                <option value="{{ $facility->id }}" @selected(old('facility_id', $editing?->facility_id) == $facility->id)>{{ $facility->nama }}</option>
                            @endforeach
                        </select>
                        @error('facility_id') <div class="os-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="os-field">
                        <label class="os-label">Tahun</label>
                        <input type="number" class="os-input" value="{{ $tahun }}" disabled>
                    </div>
                    <div class="os-field">
                        <label class="os-label" for="jumlah_anggaran">Jumlah pagu</label>
                        <input id="jumlah_anggaran" type="number" min="0" step="0.01" name="jumlah_anggaran"
                               value="{{ old('jumlah_anggaran', $editing?->jumlah_anggaran ?? 0) }}" class="os-input">
                        @error('jumlah_anggaran') <div class="os-error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="os-floating os-section-tight">
                    <input type="text" name="keterangan" placeholder=" " value="{{ old('keterangan', $editing?->keterangan) }}" class="os-floating-input">
                    <label class="os-floating-label">Keterangan revisi (opsional)</label>
                </div>
                <div class="os-section os-toolbar">
                    <button type="submit" class="os-btn os-btn--primary">
                        <x-icon name="save-line" /> Simpan pagu
                    </button>
                    <a href="{{ route('anggaran.index', ['tahun' => $tahun]) }}" class="os-btn os-btn--secondary">Batal</a>
                </div>
                @if ($editing)
                    <div class="os-help os-section-tight">Revisi menaikkan versi pagu. Riwayat versi sebelumnya tidak disimpan terpisah.</div>
                @endif
            </form>
        </div>
    @endif

    <div x-ref="list" @click="onListClick($event)" :class="{ 'opacity-60 pointer-events-none': loading }">
        @include('pages.anggaran.partials.summary')
    </div>
    <div class="os-help os-section">
        Komitmen = total PO yang belum dibatalkan. Realisasi diakui saat penerimaan barang,
        sehingga nilai yang sudah diterima berpindah dari komitmen ke realisasi dan tidak dihitung dua kali.
        Untuk unit puskesmas, komitmen dan realisasi baru terhubung setelah modul distribusi tersedia.
    </div>
@endsection
