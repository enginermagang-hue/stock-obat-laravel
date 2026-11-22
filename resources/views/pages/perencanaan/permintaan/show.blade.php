@extends('layouts.app')

@section('title', $title)

@section('content')
    @php
        $status = $permintaan->status;
        $editable = $status === \App\Enums\StatusPermintaan::Draft;
        $verifiable = in_array($status, [\App\Enums\StatusPermintaan::Dikirim, \App\Enums\StatusPermintaan::Diterima], true);
        $statusClass = match ($status) {
            \App\Enums\StatusPermintaan::Disetujui,
            \App\Enums\StatusPermintaan::Selesai => 'os-status-pill--success',
            \App\Enums\StatusPermintaan::Ditolak,
            \App\Enums\StatusPermintaan::Dibatalkan => 'os-status-pill--danger',
            \App\Enums\StatusPermintaan::Sebagian => 'os-status-pill--warning',
            default => 'os-status-pill--process',
        };
    @endphp

    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">{{ $permintaan->no_permintaan }}</h1>
            <p class="os-page-subtitle">
                {{ $permintaan->facility?->nama }} · sumber {{ $permintaan->sumber->value }} · status {{ $status->value }}
            </p>
        </div>
        <div class="os-toolbar">
            <a href="{{ route('permintaan.index') }}" class="os-btn os-btn--secondary">
                <x-icon name="arrow-left-line" /> Kembali
            </a>
            @can('create-permintaan')
                @if ($editable)
                    <a href="{{ route('permintaan.edit', $permintaan) }}" class="os-btn os-btn--secondary">
                        <x-icon name="edit-line" /> Ubah
                    </a>
                @endif
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="os-alert os-alert--danger os-section-tight">{{ $errors->first() }}</div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-card-head">
            <div>
                <div class="os-card-title">Informasi permintaan</div>
                <div class="os-card-subtitle">
                    Status <span class="os-status-pill {{ $statusClass }}">{{ $status->value }}</span>
                </div>
            </div>
            <div class="os-toolbar">
                @if ($status === \App\Enums\StatusPermintaan::Draft)
                    <form method="POST" action="{{ route('permintaan.submit', $permintaan) }}">
                        @csrf
                        <button type="submit" class="os-btn os-btn--sm os-btn--primary">
                            <x-icon name="send-plane-line" /> Kirim
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="os-card-body os-form-grid">
            <div class="os-field">
                <span class="os-label">Puskesmas</span>
                <div>{{ $permintaan->facility?->nama ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Sumber</span>
                <div>{{ $permintaan->sumber->value }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Periode</span>
                <div>
                    {{ $permintaan->periode_awal?->format('d-m-Y') ?? '—' }}
                    –
                    {{ $permintaan->periode_akhir?->format('d-m-Y') ?? '—' }}
                </div>
            </div>
            <div class="os-field">
                <span class="os-label">Tanggal</span>
                <div>{{ $permintaan->tanggal?->format('d-m-Y') ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Dokumen LPLPO</span>
                <div>
                    @if ($permintaan->lplpo)
                        <a href="{{ route('lplpo.show', $permintaan->lplpo) }}" class="font-medium underline">{{ $permintaan->lplpo->no_lplpo }}</a>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="os-field">
                <span class="os-label">Diverifikasi oleh</span>
                <div>{{ $permintaan->verifier?->name ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Tanggal verifikasi</span>
                <div>{{ $permintaan->verified_at?->format('d-m-Y H:i') ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Catatan verifikasi</span>
                <div>{{ $permintaan->catatan_verifikasi ?: '—' }}</div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('permintaan.verify', $permintaan) }}">
        @csrf

        <div class="os-section os-card os-card--flush">
            <div class="os-card-head">
                <div>
                    <div class="os-card-title">Item permintaan</div>
                    <div class="os-card-subtitle">{{ $permintaan->items->count() }} item</div>
                </div>
            </div>
            <div class="os-table-wrap">
                <table class="os-table">
                    <thead>
                    <tr>
                        <th>Obat</th>
                        <th class="os-td-right">Diminta</th>
                        <th class="os-td-right">Disetujui</th>
                        <th>Status item</th>
                        <th>Keterangan</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($permintaan->items as $item)
                        <tr>
                            <td>
                                <div class="font-medium text-heading">{{ $item->obat?->nama }}</div>
                                <div class="text-[13px] text-faint">{{ $item->obat?->kode_obat }}</div>
                            </td>
                            <td class="os-td-right">{{ $item->jumlah_diminta }}</td>
                            <td class="os-td-right">
                                @can('verify-permintaan')
                                    @if ($verifiable)
                                        <input type="number" min="0" max="{{ $item->jumlah_diminta }}"
                                               name="decisions[{{ $item->id }}][jumlah_disetujui]"
                                               value="{{ old('decisions.'.$item->id.'.jumlah_disetujui', $item->jumlah_disetujui ?? $item->jumlah_diminta) }}"
                                               class="os-input os-td-right" style="width:100px">
                                    @else
                                        {{ $item->jumlah_disetujui ?? '—' }}
                                    @endif
                                @else
                                    {{ $item->jumlah_disetujui ?? '—' }}
                                @endcan
                            </td>
                            <td>
                                @if ($item->status_item)
                                    <span class="os-status-pill {{ in_array($item->status_item->value, ['disetujui', 'selesai'], true) ? 'os-status-pill--success' : ($item->status_item->value === 'ditolak' ? 'os-status-pill--danger' : 'os-status-pill--warning') }}">
                                        {{ $item->status_item->value }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @can('verify-permintaan')
                                    @if ($verifiable)
                                        <input type="hidden" name="decisions[{{ $item->id }}][status_item]" value="{{ $item->status_item?->value }}">
                                        <input type="text" name="decisions[{{ $item->id }}][keterangan]" value="{{ old('decisions.'.$item->id.'.keterangan', $item->keterangan) }}" class="os-input">
                                    @else
                                        {{ $item->keterangan ?: '—' }}
                                    @endif
                                @else
                                    {{ $item->keterangan ?: '—' }}
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="os-empty">Belum ada item permintaan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @can('verify-permintaan')
            @if ($verifiable)
                <div class="os-section os-card os-card-body">
                    <div class="os-floating">
                        <input type="text" name="catatan_verifikasi" placeholder=" " value="{{ old('catatan_verifikasi') }}" class="os-floating-input">
                        <label class="os-floating-label">Catatan verifikasi</label>
                    </div>
                    <div class="os-section">
                        <button type="submit" class="os-btn os-btn--primary">
                            <x-icon name="check-line" /> Simpan Verifikasi
                        </button>
                    </div>
                </div>
            @endif
        @endcan
    </form>
@endsection
