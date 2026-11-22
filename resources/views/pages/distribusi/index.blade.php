@extends('layouts.app')

@section('title', 'Distribusi')

@section('content')
    @php
        $flow = ['draft', 'disetujui', 'dikirim', 'diterima'];
    @endphp

    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Distribusi</h1>
            <p class="os-page-subtitle">Pemenuhan permintaan dengan FEFO, surat jalan, dan konfirmasi terima.</p>
        </div>
        @can('create-distribution')
            <a href="{{ route('distribusi.create') }}" class="os-btn os-btn--primary">
                <x-icon name="add-line" /> Buat Surat Jalan
            </a>
        @endcan
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    <div class="os-section os-card os-card--flush"
         x-data="ajaxFilter({ endpoint: '{{ route('distribusi.index') }}' })">
        <form method="GET" x-ref="filterForm" class="os-filter-bar" @submit.prevent="submit()">
            <div class="flex w-full flex-wrap items-center gap-3">
                <div class="os-search-wrap">
                    <x-icon name="search-line" class="text-faint" />
                    <input type="text" name="search" value="{{ $filters['search'] }}"
                           placeholder="Cari no. surat / permintaan / puskesmas..." class="os-search"
                           @input.debounce.400ms="load(1)">
                </div>
                <select name="status" class="os-filter-select" style="min-width: 170px" @change="load(1)">
                    <option value="">Semua status</option>
                    @foreach ($flow as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                    @endforeach
                    <option value="dibatalkan" @selected($filters['status'] === 'dibatalkan')>dibatalkan</option>
                </select>
                <button type="submit" class="os-btn os-btn--sm os-btn--secondary">
                    <x-icon name="search-line" /> Terapkan
                </button>
                @if (array_filter($filters))
                    <button type="button" class="os-btn os-btn--sm os-btn--ghost" @click="reset()">Reset</button>
                @endif
            </div>

            <div class="flex w-full flex-wrap items-center gap-3">
                <span class="os-label mb-0">Tanggal</span>
                <div class="relative" style="min-width: 240px">
                    <input type="text" data-date-range
                           data-from="#filter-from" data-to="#filter-to"
                           data-default="{{ $filters['from'] }},{{ $filters['to'] }}"
                           placeholder="Rentang tanggal surat" class="os-filter-select w-full" readonly
                           @os-date-range="load(1)">
                    <input type="hidden" id="filter-from" name="from" value="{{ $filters['from'] }}">
                    <input type="hidden" id="filter-to" name="to" value="{{ $filters['to'] }}">
                </div>

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
            @include('pages.distribusi.partials.list')
        </div>
    </div>
@endsection
