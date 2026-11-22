@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
{{-- Dashboard --}}
<div>
    <h1 class="os-page-title">Dashboard</h1>
    <p class="os-page-subtitle">Ringkasan stok, batch, dan aktivitas gudang hari ini.</p>

    <div class="os-section os-dash-grid">
        {{-- Banner --}}
        <div class="os-col-6 os-card os-card-body">
            <div class="os-banner">
                <div>
                    <h2 class="text-[24px] font-medium text-heading">Selamat datang di ObatStock 🎉</h2>
                    <p class="mt-2 text-[15px] text-muted">Pantau stok batch, kedaluwarsa, dan distribusi obat dari satu tempat.</p>
                    <p class="text-[15px] text-muted">{{ $mutasiHariIni }} mutasi stok tercatat hari ini.</p>
                    <a href="{{ route('stok.batch') }}" class="os-btn os-btn--primary os-section-tight">
                        <x-icon name="archive-2-line" /> Lihat Batch
                    </a>
                </div>
                <div class="os-banner-art hidden w-40 sm:grid">
                    <x-icon name="heart-pulse-line" class="os-icon--xl text-primary" />
                </div>
            </div>
        </div>

        {{-- KPI --}}
        <div class="os-col-2 os-card os-kpi-card">
            <div class="os-kpi-head">
                <div class="os-kpi-icon"><x-icon name="capsule-line" /></div>
            </div>
            <div class="os-kpi-value">{{ $totalBatch }}</div>
            <div class="os-kpi-label">Batch stok</div>
            <span class="os-kpi-chip">{{ $totalObat }} jenis obat</span>
        </div>
        <div class="os-col-2 os-card os-kpi-card">
            <div class="os-kpi-head">
                <div class="os-kpi-icon os-kpi-icon--warning"><x-icon name="alert-line" /></div>
            </div>
            <div class="os-kpi-value">{{ $stokMenipis }}</div>
            <div class="os-kpi-label">Stok menipis</div>
            <span class="os-kpi-chip">≤ stok minimum</span>
        </div>
        <div class="os-col-2 os-card os-kpi-card">
            <div class="os-kpi-head">
                <div class="os-kpi-icon os-kpi-icon--danger"><x-icon name="time-line" /></div>
            </div>
            <div class="os-kpi-value">{{ $expiring }}</div>
            <div class="os-kpi-label">Mendekati expired</div>
            <span class="os-kpi-chip">90 hari</span>
        </div>

        {{-- Baris kedua --}}
        <div class="os-col-6 os-card os-card-body">
            <h2 class="os-card-title">Mutasi stok</h2>
            <p class="os-card-subtitle">Jurnal ledger masuk dan keluar</p>
            <div class="os-chart os-section-tight">[ Grafik mutasi — Fase 2 ]</div>
        </div>
        <div class="os-col-3 os-card os-card-body">
            <h2 class="os-card-title">Menunggu verifikasi</h2>
            <p class="os-card-subtitle">LPLPO dan permintaan obat</p>
            <div class="os-section-tight">
                <div class="os-metric-value">{{ $menungguVerifikasi }}</div>
                <div class="os-metric-label">LPLPO / permintaan</div>
            </div>
            <a href="{{ route('lplpo.index') }}" class="os-btn os-btn--primary os-section-tight">
                <x-icon name="arrow-right-line" /> Lihat Detail
            </a>
        </div>
        <div class="os-col-3 os-card os-card-body">
            <h2 class="os-card-title">Komposisi stok</h2>
            <p class="os-card-subtitle">Aktif, menipis, expired</p>
            <div class="os-progress-stack os-section-tight">
                <span class="os-progress-segment" style="width:70%">70%</span>
                <span class="os-progress-segment bg-warning" style="width:20%">20%</span>
                <span class="os-progress-segment bg-danger" style="width:10%">10%</span>
            </div>
            <p class="os-card-subtitle os-section-tight">Contoh komposisi — data ledger hadir di Fase 2.</p>
        </div>

        {{-- Baris ketiga --}}
        <div class="os-col-5 os-card os-card--flush">
            <div class="os-card-head">
                <div>
                    <div class="os-card-title">Data obat terbaru</div>
                    <div class="os-card-subtitle">Lima master obat pertama</div>
                </div>
                <a class="os-btn os-btn--sm os-btn--ghost" href="{{ route('master.obat') }}">Lihat semua</a>
            </div>
            <div class="os-table-wrap">
                <table class="os-table">
                    <thead><tr><th>Obat</th><th>Kode</th><th>Stok min</th></tr></thead>
                    <tbody>
                    @forelse ($obatTerbaru as $obat)
                        <tr>
                            <td>{{ $obat->nama }}</td>
                            <td>{{ $obat->kode_obat }}</td>
                            <td>{{ $obat->stok_minimum }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="os-empty">Belum ada obat.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="os-col-2 os-card os-kpi-card">
            <div class="os-kpi-value">{{ $totalPuskesmas }}</div>
            <div class="os-kpi-label">Puskesmas</div>
            <span class="os-kpi-chip">Terdaftar</span>
        </div>
        <div class="os-col-2 os-card os-kpi-card">
            <div class="os-kpi-value">{{ $totalSumberDana }}</div>
            <div class="os-kpi-label">Sumber dana</div>
            <span class="os-kpi-chip">Aktif</span>
        </div>
        @can('manage-anggaran')
            <div class="os-col-3 os-card os-card-body">
                <h2 class="os-card-title">Penyerapan anggaran</h2>
                <p class="os-card-subtitle">Komitmen + realisasi · RS {{ now()->year }}</p>
                @forelse ($penyerapan as $row)
                    <div class="os-section-tight">
                        <div class="os-metric-label flex justify-between">
                            <span>{{ $row['nama'] }}</span>
                            <span>{{ number_format($row['serapan'], 1, ',', '.') }}%</span>
                        </div>
                        <div class="os-progress">
                            <span style="width:{{ max(0, min(100, $row['serapan'])) }}%"></span>
                        </div>
                    </div>
                @empty
                    <p class="os-help os-section-tight">Belum ada pagu anggaran.</p>
                @endforelse
                @if ($anggaran)
                    <div class="os-help os-section-tight">
                        Sisa pagu RS: Rp {{ number_format($anggaran['sisa'], 0, ',', '.') }}
                    </div>
                @endif
                <a href="{{ route('anggaran.index') }}" class="os-btn os-btn--sm os-btn--ghost os-section-tight">
                    Kelola anggaran
                </a>
            </div>
        @endcan

        {{-- Baris keempat --}}
        <div class="os-col-4 os-card os-card-body">
            <h2 class="os-card-title">Tren pemakaian</h2>
            <div class="os-chart os-section-tight">[ Tren 12 bulan — Fase 3 ]</div>
        </div>
        <div class="os-col-4 os-card os-card-body">
            <h2 class="os-card-title">Penerimaan per hari</h2>
            <div class="os-chart os-section-tight">[ Grafik penerimaan — Fase 2 ]</div>
        </div>
        <div class="os-col-4 os-card os-card-body">
            <h2 class="os-card-title">Aktivitas terbaru</h2>
            <div class="os-timeline os-section-tight">
                <div class="os-timeline-item">
                    <div class="os-timeline-dot"></div>
                    <div class="text-[13px] text-heading">Master obat diperbarui</div>
                    <div class="os-timeline-time">Hari ini</div>
                </div>
                <div class="os-timeline-item">
                    <div class="os-timeline-dot"></div>
                    <div class="text-[13px] text-heading">Seeder role & permission dijalankan</div>
                    <div class="os-timeline-time">Kemarin</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
