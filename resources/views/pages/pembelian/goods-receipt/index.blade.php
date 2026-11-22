@extends('layouts.app')

@section('title', 'Penerimaan Barang')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Penerimaan Barang</h1>
            <p class="os-page-subtitle">Riwayat penerimaan PO: batch, stok masuk, dan ledger.</p>
        </div>
        <a href="{{ route('pembelian.penerimaan.create') }}" class="os-btn os-btn--primary">
            <x-icon name="add-line" /> Buat Penerimaan
        </a>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    <div class="os-section os-card os-card--flush"
         x-data="ajaxFilter({ endpoint: '{{ route('pembelian.penerimaan') }}' })">
        <form method="GET" x-ref="filterForm" class="os-filter-bar" @submit.prevent="submit()">
            <div class="os-search-wrap">
                <x-icon name="search-line" class="text-faint" />
                <input type="text" name="search" value="{{ $filters['search'] }}"
                       placeholder="Cari no. penerimaan / PO / supplier..." class="os-search"
                       @input.debounce.400ms="load(1)">
            </div>
            <select name="supplier" class="os-filter-select" style="min-width: 200px" @change="load(1)">
                <option value="">Semua supplier</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected($filters['supplier'] === $supplier->id)>{{ $supplier->nama }}</option>
                @endforeach
            </select>
            <select name="sumber_dana" class="os-filter-select" style="min-width: 200px" @change="load(1)">
                <option value="">Semua sumber dana</option>
                @foreach ($sumberDana as $dana)
                    <option value="{{ $dana->id }}" @selected($filters['sumber_dana'] === $dana->id)>{{ $dana->nama }}</option>
                @endforeach
            </select>
            <div class="relative" style="min-width: 240px">
                <input type="text" data-date-range
                       data-from="#filter-from" data-to="#filter-to"
                       data-default="{{ $filters['from'] }},{{ $filters['to'] }}"
                       placeholder="Rentang tanggal terima" class="os-filter-select w-full" readonly
                       @os-date-range="load(1)">
                <input type="hidden" id="filter-from" name="from" value="{{ $filters['from'] }}">
                <input type="hidden" id="filter-to" name="to" value="{{ $filters['to'] }}">
            </div>
            <button type="submit" class="os-btn os-btn--sm os-btn--secondary">
                <x-icon name="search-line" /> Terapkan
            </button>
            @if (array_filter($filters))
                <button type="button" class="os-btn os-btn--sm os-btn--ghost" @click="reset()">Reset</button>
            @endif
        </form>

        <div x-ref="list" @click="onListClick($event)" :class="{ 'opacity-60 pointer-events-none': loading }">
            @include('pages.pembelian.goods-receipt.partials.list')
        </div>
    </div>
@endsection
