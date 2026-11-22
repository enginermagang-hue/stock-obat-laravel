@extends('layouts.app')

@section('title', 'Stock Ledger')

@section('content')
    <h1 class="os-page-title">Stock Ledger</h1>
    <p class="os-page-subtitle">Jurnal permanen seluruh mutasi stok. Append-only, tidak dapat diubah.</p>

    <div class="os-section os-card os-card--flush"
         x-data="ajaxFilter({ endpoint: '{{ route('stok.ledger') }}' })">
        <form method="GET" x-ref="filterForm" class="os-filter-bar" @submit.prevent="submit()">
            <div class="os-search-wrap">
                <x-icon name="search-line" class="text-faint" />
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari obat / batch..." class="os-search"
                       @input.debounce.400ms="load(1)">
            </div>
            <select name="tipe" class="os-filter-select" @change="load(1)">
                <option value="">Semua tipe</option>
                <option value="masuk" @selected(request('tipe') === 'masuk')>Masuk</option>
                <option value="keluar" @selected(request('tipe') === 'keluar')>Keluar</option>
            </select>
            <select name="referensi" class="os-filter-select" @change="load(1)">
                <option value="">Semua referensi</option>
                <option value="penerimaan" @selected(request('referensi') === 'penerimaan')>Penerimaan</option>
                <option value="penyesuaian" @selected(request('referensi') === 'penyesuaian')>Penyesuaian</option>
                <option value="opname" @selected(request('referensi') === 'opname')>Opname</option>
                <option value="distribusi" @selected(request('referensi') === 'distribusi')>Distribusi</option>
            </select>
            <button type="submit" class="os-btn os-btn--sm os-btn--secondary">Cari</button>
            @if (request()->filled('search') || request()->filled('tipe') || request()->filled('referensi'))
                <button type="button" class="os-btn os-btn--sm os-btn--ghost" @click="reset()">Reset</button>
            @endif
        </form>
        <div x-ref="list" @click="onListClick($event)" :class="{ 'opacity-60 pointer-events-none': loading }">
            @include('pages.stok.partials.ledger-list')
        </div>
    </div>
@endsection
