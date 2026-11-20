<div>
    <h1 class="os-page-title">Prediksi Kebutuhan Obat</h1>
    <p class="os-page-subtitle">Holt-Winters PHP dengan fallback historis. Rekomendasi tetap disetujui apoteker.</p>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    <div class="os-section os-card os-card-body">
        <div class="os-form-grid">
            <div class="os-field">
                <label class="os-label">Periode bulan</label>
                <select wire:model="periode_bulan" class="os-select">
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="os-field">
                <label class="os-label">Periode tahun</label>
                <input type="number" wire:model="periode_tahun" class="os-input">
            </div>
            @unless (auth()->user()->facility_id)
                <div class="os-field">
                    <label class="os-label">Fasilitas</label>
                    <select wire:model="facility_id" class="os-select">
                        <option value="">RS (gabungan)</option>
                        @foreach ($facilities as $facility)
                            <option value="{{ $facility->id }}">{{ $facility->nama }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
        </div>
        <div class="os-section">
            <button wire:click="run" class="os-btn os-btn--primary">
                <x-icon name="line-chart-line" :size="16" /> Jalankan Prediksi
            </button>
        </div>
        <div class="os-help os-section-tight">Butuh minimal 12 bulan LPLPO terverifikasi. Jika kurang, sistem memakai rata-rata historis.</div>
    </div>

    <div class="os-section grid gap-4 sm:grid-cols-3">
        <div class="os-kpi-card">
            <div class="os-kpi-value">{{ $summary->total ?? 0 }}</div>
            <div class="os-kpi-label">Prediksi periode ini</div>
        </div>
        <div class="os-kpi-card">
            <div class="os-kpi-value">{{ $summary->avg_mape ? number_format((float) $summary->avg_mape, 1) . '%' : '—' }}</div>
            <div class="os-kpi-label">Rata-rata MAPE</div>
        </div>
        <div class="os-kpi-card">
            <div class="os-kpi-value">{{ $summary->low_confidence ?? 0 }}</div>
            <div class="os-kpi-label">Confidence rendah</div>
        </div>
    </div>

    <div class="os-section os-card os-card--flush">
        <div class="os-table-wrap">
            <table class="os-table os-table--striped os-table--hover">
                <thead><tr><th>Obat</th><th>Fasilitas</th><th>Periode</th><th class="os-td-right">Prediksi</th><th>Interval</th><th class="os-td-right">Confidence</th><th>Metode</th><th class="os-td-right">MAPE</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($predictions as $prediction)
                    <tr>
                        <td>{{ $prediction->obat?->nama }}</td>
                        <td>{{ $prediction->facility?->nama ?? 'RS / gabungan' }}</td>
                        <td>{{ $prediction->periode_bulan }} / {{ $prediction->periode_tahun }}</td>
                        <td class="os-td-right">{{ $prediction->nilai_prediksi }}</td>
                        <td>{{ $prediction->interval_bawah }} – {{ $prediction->interval_atas }}</td>
                        <td class="os-td-right">{{ number_format((float) $prediction->confidence_score, 2) }}</td>
                        <td><span class="os-badge">{{ $prediction->metode_model->value }}</span></td>
                        <td class="os-td-right">{{ $prediction->mape !== null ? $prediction->mape.'%' : '—' }}</td>
                        <td>
                            <span class="os-status-pill {{ in_array($prediction->status->value, ['divalidasi', 'dipakai']) ? 'os-status-pill--success' : ($prediction->status->value === 'ditolak' ? 'os-status-pill--danger' : 'os-status-pill--process') }}">
                                {{ $prediction->status->value }}
                            </span>
                        </td>
                        <td class="os-td-right">
                            @if ($prediction->status === \App\Enums\StatusPrediksi::Draft)
                                <button wire:click="validatePrediction({{ $prediction->id }})" class="os-btn os-btn--sm os-btn--secondary">Validasi</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="os-empty">Belum ada prediksi. Jalankan prediksi untuk periode terpilih.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="os-pagination">{{ $predictions->links() }}</div>
    </div>
</div>
