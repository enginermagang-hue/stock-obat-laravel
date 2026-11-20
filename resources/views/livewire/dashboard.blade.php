<div>
    <h1 class="os-page-title">Dashboard</h1>
    <p class="os-page-subtitle">Ringkasan data fondasi. Kartu stok dan anggaran tersedia setelah Fase 2 dan Fase 6.</p>

    <div class="os-section os-kpi-grid">
        <div class="os-kpi-card os-kpi-card--primary">
            <div class="os-kpi-value">{{ $totalObat }}</div>
            <div class="os-kpi-label">Jenis obat aktif</div>
        </div>
        <div class="os-kpi-card os-kpi-card--info">
            <div class="os-kpi-value">{{ $totalPuskesmas }}</div>
            <div class="os-kpi-label">Puskesmas terdaftar</div>
        </div>
        <div class="os-kpi-card os-kpi-card--warning">
            <div class="os-kpi-value">{{ $totalSumberDana }}</div>
            <div class="os-kpi-label">Sumber dana</div>
        </div>
    </div>

    <div class="os-section os-grid-2">
        <div class="os-card os-card-body">
            <h2 class="os-card-title">Tugas menunggu</h2>
            <p class="os-card-subtitle os-section-tight">Modul LPLPO, permintaan, dan opname masuk pada Fase 2–3.</p>
        </div>
        <div class="os-card os-card-body">
            <h2 class="os-card-title">Tren pemakaian obat</h2>
            <div class="os-placeholder os-section-tight">[ Grafik pemakaian — Fase 3 ]</div>
        </div>
    </div>
</div>
