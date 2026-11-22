<div class="os-section os-card os-card--flush">
    <div class="os-kpi-grid os-section">
        <div class="os-kpi-card">
            <div class="os-kpi-head">
                <div class="os-kpi-icon"><x-icon name="wallet-3-line" /></div>
                <span class="os-kpi-chip">Pagu</span>
            </div>
            <div class="os-kpi-value">Rp {{ number_format($report['pagu'], 0, ',', '.') }}</div>
            <div class="os-kpi-label">Total pagu {{ $tahun }}</div>
        </div>
        <div class="os-kpi-card">
            <div class="os-kpi-head">
                <div class="os-kpi-icon os-kpi-icon--warning"><x-icon name="shopping-bag-3-line" /></div>
                <span class="os-kpi-chip">Komitmen</span>
            </div>
            <div class="os-kpi-value">Rp {{ number_format($report['komitmen'], 0, ',', '.') }}</div>
            <div class="os-kpi-label">PO belum diterima</div>
        </div>
        <div class="os-kpi-card">
            <div class="os-kpi-head">
                <div class="os-kpi-icon os-kpi-icon--success"><x-icon name="check-double-line" /></div>
                <span class="os-kpi-chip">Realisasi</span>
            </div>
            <div class="os-kpi-value">Rp {{ number_format($report['realisasi'], 0, ',', '.') }}</div>
            <div class="os-kpi-label">Nilai penerimaan barang</div>
        </div>
        <div class="os-kpi-card">
            <div class="os-kpi-head">
                <div class="os-kpi-icon {{ $report['sisa'] < 0 ? 'os-kpi-icon--danger' : 'os-kpi-icon--info' }}">
                    <x-icon name="safe-2-line" />
                </div>
                <span class="os-kpi-chip">Sisa</span>
            </div>
            <div class="os-kpi-value">Rp {{ number_format($report['sisa'], 0, ',', '.') }}</div>
            <div class="os-kpi-label">Pagu − komitmen − realisasi</div>
        </div>
    </div>
</div>

<div class="os-section os-card os-card--flush">
    <div class="os-table-wrap">
        <table class="os-table os-table--hover">
            <thead>
            <tr>
                <th>Sumber dana</th>
                <th>Unit</th>
                <th class="os-td-right">Pagu</th>
                <th class="os-td-right">Komitmen</th>
                <th class="os-td-right">Realisasi</th>
                <th class="os-td-right">Sisa</th>
                <th>Serapan</th>
                <th>Versi</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($report['rows'] as $row)
                @php($anggaran = $row['anggaran'])
                <tr>
                    <td>{{ $anggaran->sumberDana?->nama }}</td>
                    <td>
                        <span class="os-badge">{{ $anggaran->unitLabel() }}</span>
                    </td>
                    <td class="os-td-right">Rp {{ number_format($row['pagu'], 0, ',', '.') }}</td>
                    <td class="os-td-right">Rp {{ number_format($row['komitmen_bersih'], 0, ',', '.') }}</td>
                    <td class="os-td-right">Rp {{ number_format($row['realisasi'], 0, ',', '.') }}</td>
                    <td class="os-td-right {{ $row['sisa'] < 0 ? 'text-danger' : '' }}">
                        Rp {{ number_format($row['sisa'], 0, ',', '.') }}
                    </td>
                    <td>
                        <div class="os-progress">
                            <span style="width: {{ max(0, min(100, $row['persen_realisasi'])) }}%"></span>
                        </div>
                        <div class="os-help">
                            {{ number_format($row['persen_realisasi'], 1, ',', '.') }}% realisasi
                            · {{ number_format($row['persen_komitmen'], 1, ',', '.') }}% komitmen
                        </div>
                    </td>
                    <td>{{ $anggaran->versi }}</td>
                    <td class="os-td-right">
                        <div class="os-table-actions">
                            @can('manage-anggaran')
                                <a href="{{ route('anggaran.index', ['tahun' => $tahun, 'edit' => $anggaran->id, 'form' => 1]) }}"
                                   class="os-btn os-btn--sm os-btn--secondary">Revisi</a>
                                <form method="POST" action="{{ route('anggaran.destroy', $anggaran) }}"
                                      onsubmit="return confirm('Hapus pagu ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="os-btn os-btn--sm os-btn--danger">Hapus</button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="os-empty">Belum ada pagu untuk filter ini.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
