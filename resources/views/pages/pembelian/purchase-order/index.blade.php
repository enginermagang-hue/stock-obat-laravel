@extends('layouts.app')

@section('title', 'Purchase Order')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Purchase Order</h1>
            <p class="os-page-subtitle">Komitmen pembelian dan penerimaan barang.</p>
        </div>
        <a href="{{ route('pembelian.purchase-order.create') }}" class="os-btn os-btn--primary">
            <x-icon name="add-line" /> Buat PO
        </a>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    @error('status')
        <div class="os-alert os-alert--danger os-section-tight">{{ $message }}</div>
    @enderror

    <div class="os-section os-card os-card--flush"
         x-data="ajaxFilter({ endpoint: '{{ route('pembelian.purchase-order') }}' })">
        <form method="GET" x-ref="filterForm" class="os-filter-bar" @submit.prevent="submit()">
            <div class="os-search-wrap">
                <x-icon name="search-line" class="text-faint" />
                <input type="text" name="search" placeholder="Cari no. PO / supplier..." class="os-search"
                       @input.debounce.400ms="load(1)">
            </div>
            <button type="submit" class="os-btn os-btn--sm os-btn--secondary">
                <x-icon name="search-line" /> Terapkan
            </button>
        </form>
        <div x-ref="list" @click="onListClick($event)" :class="{ 'opacity-60 pointer-events-none': loading }">
            @include('pages.pembelian.purchase-order.partials.list')
        </div>
    </div>
@endsection
