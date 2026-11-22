@php
    $statusBadge = fn ($status) => match ($status) {
        \App\Enums\StatusPermintaan::Disetujui,
        \App\Enums\StatusPermintaan::Selesai => 'os-status-pill--success',
        \App\Enums\StatusPermintaan::Ditolak,
        \App\Enums\StatusPermintaan::Dibatalkan => 'os-status-pill--danger',
        \App\Enums\StatusPermintaan::Sebagian => 'os-status-pill--warning',
        default => 'os-status-pill--process',
    };
@endphp

<div class="os-table-wrap">
    <table class="os-table os-table--hover">
        <thead>
        <tr>
            <th>No. permintaan</th>
            <th>Puskesmas</th>
            <th>Sumber</th>
            <th>Periode</th>
            <th class="os-td-right">Item</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse ($permintaans as $permintaan)
            <tr>
                <td>{{ $permintaan->no_permintaan }}</td>
                <td>{{ $permintaan->facility?->nama }}</td>
                <td><span class="os-badge">{{ $permintaan->sumber->value }}</span></td>
                <td>
                    @if ($permintaan->periode_awal || $permintaan->periode_akhir)
                        {{ $permintaan->periode_awal?->format('d-m-Y') ?? '—' }}
                        –
                        {{ $permintaan->periode_akhir?->format('d-m-Y') ?? '—' }}
                    @else
                        —
                    @endif
                </td>
                <td class="os-td-right">{{ $permintaan->items_count }}</td>
                <td>
                    <span class="os-status-pill {{ $statusBadge($permintaan->status) }}">
                        {{ $permintaan->status->value }}
                    </span>
                </td>
                <td class="os-td-right">
                    <a href="{{ route('permintaan.show', $permintaan) }}" class="os-btn os-btn--sm os-btn--secondary">Lihat</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="os-empty">Belum ada permintaan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="os-pagination">{{ $permintaans->links() }}</div>
