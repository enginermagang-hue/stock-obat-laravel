@extends('layouts.app')

@section('title', 'LPLPO')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">LPLPO</h1>
            <p class="os-page-subtitle">Laporan pemakaian dan stok obat per puskesmas.</p>
        </div>
        @can('create-lplpo')
            <a href="{{ route('lplpo.create') }}" class="os-btn os-btn--primary">
                <x-icon name="add-line" /> Buat LPLPO
            </a>
        @endcan
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    <div class="os-section os-card os-card--flush"
         x-data="ajaxFilter({ endpoint: '{{ route('lplpo.index') }}' })">
        <form method="GET" x-ref="filterForm" class="os-filter-bar" @submit.prevent="submit()">
            <div class="flex w-full flex-wrap items-center gap-3">
                <div class="os-search-wrap">
                    <x-icon name="search-line" class="text-faint" />
                    <input type="text" name="search" value="{{ $filters['search'] }}"
                           placeholder="Cari no. LPLPO / puskesmas..." class="os-search"
                           @input.debounce.400ms="load(1)">
                </div>
                <select name="status" class="os-filter-select" style="min-width: 160px" @change="load(1)">
                    <option value="">Semua status</option>
                    @foreach (\App\Enums\StatusLplpo::cases() as $case)
                        <option value="{{ $case->value }}" @selected($filters['status'] === $case->value)>{{ $case->value }}</option>
                    @endforeach
                </select>
                <button type="submit" class="os-btn os-btn--sm os-btn--secondary">
                    <x-icon name="search-line" /> Terapkan
                </button>
                @if (array_filter($filters))
                    <button type="button" class="os-btn os-btn--sm os-btn--ghost" @click="reset()">Reset</button>
                @endif
            </div>

            <div class="flex w-full flex-wrap items-center gap-3">
                <span class="os-label mb-0">Periode</span>
                <select name="bulan" class="os-filter-select" style="min-width: 150px" @change="load(1)">
                    <option value="">Semua bulan</option>
                    @foreach ($bulanOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['bulan'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="tahun" class="os-filter-select" style="min-width: 140px" @change="load(1)">
                    <option value="">Semua tahun</option>
                    @foreach ($tahunOptions as $tahun)
                        <option value="{{ $tahun }}" @selected($filters['tahun'] === (int) $tahun)>{{ $tahun }}</option>
                    @endforeach
                </select>

                @unless (auth()->user()->facility_id)
                    <span class="os-label mb-0 ml-2">Unit</span>
                    <select name="facility" class="os-filter-select" style="min-width: 220px" @change="load(1)">
                        <option value="">Semua puskesmas</option>
                        @foreach ($facilities as $facility)
                            <option value="{{ $facility->id }}" @selected($filters['facility'] === $facility->id)>{{ $facility->kode }} · {{ $facility->nama }}</option>
                        @endforeach
                    </select>
                @endunless
            </div>
        </form>

        <div x-ref="list" @click="onListClick($event)" :class="{ 'opacity-60 pointer-events-none': loading }">
            @include('pages.perencanaan.lplpo.partials.list')
        </div>
    </div>
@endsection
