@extends('layouts.app')

@section('title', $title)

@section('content')
    @php
        $status = $lplpo->status;
        $editable = in_array($status, [\App\Enums\StatusLplpo::Draft, \App\Enums\StatusLplpo::Ditolak], true);
        $statusClass = match ($status) {
            \App\Enums\StatusLplpo::Diverifikasi => 'os-status-pill--success',
            \App\Enums\StatusLplpo::Ditolak => 'os-status-pill--danger',
            default => 'os-status-pill--process',
        };
    @endphp

    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">{{ $lplpo->no_lplpo }}</h1>
            <p class="os-page-subtitle">
                {{ $lplpo->facility?->nama }} · {{ $lplpo->bulan }}/{{ $lplpo->tahun }} · versi {{ $lplpo->versi }}
            </p>
        </div>
        <div class="os-toolbar">
            <a href="{{ route('lplpo.index') }}" class="os-btn os-btn--secondary">
                <x-icon name="arrow-left-line" /> Kembali
            </a>
            @can('create-lplpo')
                @if ($editable)
                    <a href="{{ route('lplpo.edit', $lplpo) }}" class="os-btn os-btn--secondary">
                        <x-icon name="edit-line" /> Ubah
                    </a>
                @endif
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-card-head">
            <div>
                <div class="os-card-title">Informasi LPLPO</div>
                <div class="os-card-subtitle">
                    Status <span class="os-status-pill {{ $statusClass }}">{{ str_replace('_', ' ', $status->value) }}</span>
                </div>
            </div>
            <div class="os-toolbar">
                @if ($status === \App\Enums\StatusLplpo::Draft)
                    <form method="POST" action="{{ route('lplpo.submit', $lplpo) }}">
                        @csrf
                        <button type="submit" class="os-btn os-btn--sm os-btn--primary">
                            <x-icon name="send-plane-line" /> Kirim
                        </button>
                    </form>
                @endif
                @can('verify-lplpo')
                    @if ($status === \App\Enums\StatusLplpo::Dikirim)
                        <form method="POST" action="{{ route('lplpo.verify', $lplpo) }}">
                            @csrf
                            <input type="hidden" name="approve" value="1">
                            <button type="submit" class="os-btn os-btn--sm os-btn--primary">
                                <x-icon name="check-line" /> Verifikasi
                            </button>
                        </form>
                        <form method="POST" action="{{ route('lplpo.verify', $lplpo) }}"
                              onsubmit="return confirm('Tolak LPLPO ini?')">
                            @csrf
                            <button type="submit" class="os-btn os-btn--sm os-btn--danger">
                                <x-icon name="close-line" /> Tolak
                            </button>
                        </form>
                    @endif
                @endcan
                @can('create-permintaan')
                    @if ($status === \App\Enums\StatusLplpo::Diverifikasi)
                        <form method="POST" action="{{ route('lplpo.permintaan', $lplpo) }}">
                            @csrf
                            <button type="submit" class="os-btn os-btn--sm os-btn--primary">
                                <x-icon name="send-plane-line" /> Buat Permintaan
                            </button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

        <div class="os-card-body os-form-grid">
            <div class="os-field">
                <span class="os-label">Puskesmas</span>
                <div>{{ $lplpo->facility?->nama ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Periode</span>
                <div>{{ $lplpo->bulan }} / {{ $lplpo->tahun }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Versi</span>
                <div>{{ $lplpo->versi }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Diverifikasi oleh</span>
                <div>{{ $lplpo->verifier?->name ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Tanggal verifikasi</span>
                <div>{{ $lplpo->verified_at?->format('d-m-Y H:i') ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Catatan</span>
                <div>{{ $lplpo->catatan ?: '—' }}</div>
            </div>
        </div>
    </div>

    <div class="os-section os-card os-card--flush">
        <div class="os-card-head">
            <div>
                <div class="os-card-title">Item LPLPO</div>
                <div class="os-card-subtitle">{{ $lplpo->items->count() }} item</div>
            </div>
        </div>
        <div class="os-table-wrap">
            <table class="os-table">
                <thead>
                <tr><th>Obat</th><th class="os-td-right">Stok awal</th><th class="os-td-right">Pemasukan</th><th class="os-td-right">Pemakaian</th><th class="os-td-right">Stok akhir</th><th class="os-td-right">Sistem</th><th class="os-td-right">Diminta</th></tr>
                </thead>
                <tbody>
                @foreach ($lplpo->items as $item)
                    <tr>
                        <td>{{ $item->obat?->nama }}</td>
                        <td class="os-td-right">{{ $item->stok_awal }}</td>
                        <td class="os-td-right">{{ $item->pemasukan }}</td>
                        <td class="os-td-right">{{ $item->pemakaian }}</td>
                        <td class="os-td-right">{{ $item->stok_akhir }}</td>
                        <td class="os-td-right {{ $item->stok_akhir !== $item->stok_akhir_sistem ? 'text-danger' : '' }}">{{ $item->stok_akhir_sistem }}</td>
                        <td class="os-td-right">{{ $item->jumlah_diminta }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
