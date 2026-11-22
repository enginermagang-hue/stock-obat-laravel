<div class="os-table-wrap">
    <table class="os-table os-table--hover">
        <thead><tr><th>No. LPLPO</th><th>Puskesmas</th><th>Periode</th><th>Versi</th><th class="os-td-right">Item</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($lplpos as $lplpo)
            <tr>
                <td>{{ $lplpo->no_lplpo }}</td>
                <td>{{ $lplpo->facility?->nama }}</td>
                <td>{{ $lplpo->bulan }} / {{ $lplpo->tahun }}</td>
                <td>{{ $lplpo->versi }}</td>
                <td class="os-td-right">{{ $lplpo->items_count }}</td>
                <td>
                    <span class="os-status-pill {{ $lplpo->status === \App\Enums\StatusLplpo::Diverifikasi ? 'os-status-pill--success' : ($lplpo->status === \App\Enums\StatusLplpo::Ditolak ? 'os-status-pill--danger' : 'os-status-pill--process') }}">
                        {{ $lplpo->status->value }}
                    </span>
                </td>
                <td class="os-td-right">
                    <a href="{{ route('lplpo.show', $lplpo) }}" class="os-btn os-btn--sm os-btn--secondary">Lihat</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="os-empty">Belum ada LPLPO.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="os-pagination">{{ $lplpos->links() }}</div>
