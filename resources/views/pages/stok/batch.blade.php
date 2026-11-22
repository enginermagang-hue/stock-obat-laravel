@extends('layouts.app')

@section('title', 'Batch Obat')

@section('content')
    <h1 class="os-page-title">Batch Obat</h1>
    <p class="os-page-subtitle">Stok per batch dengan urutan FEFO dan status kedaluwarsa.</p>

    <div class="os-section os-card os-card--flush"
         x-data="ajaxFilter({ endpoint: '{{ route('stok.batch') }}' })">
        <form method="GET" x-ref="filterForm" class="os-filter-bar" @submit.prevent="submit()">
            <div class="os-search-wrap">
                <x-icon name="search-line" class="text-faint" />
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari obat / no. batch..." class="os-search"
                       @input.debounce.400ms="load(1)">
            </div>
            <select name="expiry" class="os-filter-select" @change="load(1)">
                <option value="">Semua expiry</option>
                <option value="expiring" @selected(request('expiry') === 'expiring')>Mendekati expired (90 hari)</option>
                <option value="expired" @selected(request('expiry') === 'expired')>Sudah expired</option>
            </select>
            <button type="submit" class="os-btn os-btn--sm os-btn--secondary">Cari</button>
            @if (request()->filled('search') || request()->filled('expiry'))
                <button type="button" class="os-btn os-btn--sm os-btn--ghost" @click="reset()">Reset</button>
            @endif
        </form>
        <div x-ref="list" @click="onListClick($event)" :class="{ 'opacity-60 pointer-events-none': loading }">
            @include('pages.stok.partials.batch-list')
        </div>
    </div>
@endsection
