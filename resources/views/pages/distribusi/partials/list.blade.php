@php
    $badge = [
        'draft' => 'os-status-pill--draft',
        'disetujui' => 'os-status-pill--success',
        'dikirim' => 'os-status-pill--process',
        'diterima' => 'os-status-pill--success',
        'dibatalkan' => 'os-status-pill--danger',
    ];
@endphp

<div class="os-table-wrap">
    <table class="os-table os-table--hover">
        <thead>
        <tr><th>No. surat</th><th>Puskesmas tujuan</th><th>Permintaan</th><th>Tanggal</th><th class="os-td-right">Item</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($distributions as $distribution)
            <tr>
                <td>{{ $distribution->no_surat }}</td>
                <td>{{ $distribution->facility?->nama }}</td>
                <td>{{ $distribution->permintaan?->no_permintaan ?? '—' }}</td>
                <td>{{ $distribution->tanggal?->format('d-m-Y') }}</td>
                <td class="os-td-right">{{ $distribution->items_count }}</td>
                <td>
                    <span class="os-status-pill {{ $badge[$distribution->status->value] ?? 'os-status-pill--draft' }}">
                        {{ $distribution->status->value }}
                    </span>
                </td>
                <td class="os-td-right">
                    <a href="{{ route('distribusi.show', $distribution) }}" class="os-btn os-btn--sm os-btn--secondary">Lihat</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="os-empty">Belum ada surat jalan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="os-pagination">{{ $distributions->links() }}</div>
