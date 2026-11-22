@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Laporan</h1>
            <p class="os-page-subtitle">Monitoring stok, anggaran, distribusi, kepatuhan, dan akurasi prediksi.</p>
        </div>
    </div>

    <div x-data="ajaxFilter({ endpoint: '{{ route('laporan.index') }}' })">
        <div class="os-section os-toolbar" style="flex-wrap: wrap;">
            @foreach ($catalog as $key => $meta)
                <a href="{{ route('laporan.index', ['report' => $key]) }}"
                   class="os-btn {{ $report === $key ? 'os-btn--primary' : 'os-btn--secondary' }}">
                    {{ $meta['label'] }}
                </a>
            @endforeach
        </div>

        <form method="GET" x-ref="filterForm" class="os-section os-card os-card-body" @submit.prevent="submit()">
            <input type="hidden" name="report" value="{{ $report }}">
            <div class="os-form-grid">
                @unless ($report === 'audit-log')
                    <div class="os-floating">
                        <input type="number" name="tahun" placeholder=" " value="{{ $params['tahun'] }}"
                               class="os-floating-input" @change="load(1)">
                        <label class="os-floating-label">Tahun</label>
                    </div>
                @endunless

                @if (in_array($report, ['stock-card', 'tren-pemakaian'], true))
                    <div class="os-field">
                        <label class="os-label" for="obat">Obat</label>
                        <select id="obat" name="obat" class="os-select" @change="load(1)">
                            <option value="">— semua obat —</option>
                            @foreach ($obat as $item)
                                <option value="{{ $item->id }}" @selected($params['obat_id'] === $item->id)>{{ $item->nama }} ({{ $item->kode_obat }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @unless (auth()->user()->facility_id)
                    @if (in_array($report, ['lplpo-recap', 'permintaan-realisasi', 'distribusi-nilai', 'opname', 'opname-selisih', 'anggaran-realisasi'], true))
                        <div class="os-field">
                            <label class="os-label" for="facility">Unit</label>
                            <select id="facility" name="facility" class="os-select" @change="load(1)">
                                <option value="">— semua unit —</option>
                                @foreach ($facilities as $facility)
                                    <option value="{{ $facility->id }}" @selected($params['facility_id'] === $facility->id)>{{ $facility->kode }} · {{ $facility->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                @endunless

                @if ($report === 'expiry')
                    <div class="os-floating">
                        <input type="number" name="days" placeholder=" " value="{{ $params['days'] }}"
                               class="os-floating-input" @change="load(1)">
                        <label class="os-floating-label">Hari ke depan</label>
                    </div>
                @endif

                @if (in_array($report, ['stock-card', 'audit-log'], true))
                    <div class="os-floating">
                        <input type="date" name="from" placeholder=" " value="{{ $params['from'] }}"
                               class="os-floating-input" @change="load(1)">
                        <label class="os-floating-label">Dari tanggal</label>
                    </div>
                    <div class="os-floating">
                        <input type="date" name="to" placeholder=" " value="{{ $params['to'] }}"
                               class="os-floating-input" @change="load(1)">
                        <label class="os-floating-label">Sampai tanggal</label>
                    </div>
                @endif

                @if ($report === 'audit-log')
                    <div class="os-field">
                        <label class="os-label" for="event">Event</label>
                        <select id="event" name="event" class="os-select" @change="load(1)">
                            <option value="">Semua event</option>
                            <option value="created" @selected($params['event'] === 'created')>created</option>
                            <option value="updated" @selected($params['event'] === 'updated')>updated</option>
                            <option value="deleted" @selected($params['event'] === 'deleted')>deleted</option>
                        </select>
                    </div>
                    <div class="os-floating">
                        <input type="text" name="search" placeholder=" " value="{{ $params['search'] }}"
                               class="os-floating-input" @input.debounce.400ms="load(1)">
                        <label class="os-floating-label">Cari subjek / deskripsi</label>
                    </div>
                @endif
            </div>

            <div class="os-section os-toolbar">
                <button type="submit" class="os-btn os-btn--primary">
                    <x-icon name="search-line" /> Terapkan
                </button>
            </div>
        </form>

        <div x-ref="list" @click="onListClick($event)" :class="{ 'opacity-60 pointer-events-none': loading }">
            @include('pages.laporan.partials.list')
        </div>
    </div>
@endsection
