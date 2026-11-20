<div>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Anggaran &amp; Realisasi</h1>
            <p class="os-page-subtitle">Pagu tahunan per sumber dana dan unit, dengan komitmen PO dan realisasi penerimaan.</p>
        </div>
        <button wire:click="create" class="os-btn os-btn--primary">
            <x-icon name="add-line" :size="16" /> Tambah Pagu
        </button>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-filter-bar">
            <div class="os-field os-filter">
                <select wire:model.live="tahun" class="os-filter-select">
                    @for ($year = now()->year + 1; $year >= now()->year - 3; $year--)
                        <option value="{{ $year }}">Tahun {{ $year }}</option>
                    @endfor
                </select>
            </div>
            <div class="os-field os-filter">
                <select wire:model.live="facilityFilter" class="os-filter-select">
                    <option value="all">Semua unit</option>
                    <option value="0">Rumah Sakit (RS)</option>
                    @foreach ($facilities as $facility)
                        <option value="{{ $facility->id }}">{{ $facility->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="os-field os-filter">
                <select wire:model.live="sumber_dana_id" class="os-filter-select">
                    <option value="">Semua sumber dana</option>
                    @foreach ($sumberDana as $sumber)
                        <option value="{{ $sumber->id }}">{{ $sumber->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="os-kpi-grid os-section">
            <div class="os-kpi-card">
                <div class="os-kpi-head">
                    <div class="os-kpi-icon"><x-icon name="wallet-3-line" :size="20" /></div>
                    <span class="os-kpi-chip">Pagu</span>
                </div>
                <div class="os-kpi-value">Rp {{ number_format($report['pagu'], 0, ',', '.') }}</div>
                <div class="os-kpi-label">Total pagu {{ $tahun }}</div>
            </div>
            <div class="os-kpi-card">
                <div class="os-kpi-head">
                    <div class="os-kpi-icon os-kpi-icon--warning"><x-icon name="shopping-bag-3-line" :size="20" /></div>
                    <span class="os-kpi-chip">Komitmen</span>
                </div>
                <div class="os-kpi-value">Rp {{ number_format($report['komitmen'], 0, ',', '.') }}</div>
                <div class="os-kpi-label">PO belum diterima</div>
            </div>
            <div class="os-kpi-card">
                <div class="os-kpi-head">
                    <div class="os-kpi-icon os-kpi-icon--success"><x-icon name="check-double-line" :size="20" /></div>
                    <span class="os-kpi-chip">Realisasi</span>
                </div>
                <div class="os-kpi-value">Rp {{ number_format($report['realisasi'], 0, ',', '.') }}</div>
                <div class="os-kpi-label">Nilai penerimaan barang</div>
            </div>
            <div class="os-kpi-card">
                <div class="os-kpi-head">
                    <div class="os-kpi-icon {{ $report['sisa'] < 0 ? 'os-kpi-icon--danger' : 'os-kpi-icon--info' }}">
                        <x-icon name="safe-2-line" :size="20" />
                    </div>
                    <span class="os-kpi-chip">Sisa</span>
                </div>
                <div class="os-kpi-value">Rp {{ number_format($report['sisa'], 0, ',', '.') }}</div>
                <div class="os-kpi-label">Pagu − komitmen − realisasi</div>
            </div>
        </div>
    </div>

    @if ($showForm)
        <div class="os-section os-card os-card-body">
            <h2 class="os-card-title">{{ $editingId ? 'Revisi pagu' : 'Tambah pagu' }}</h2>
            <div class="os-section-tight os-form-grid">
                <div class="os-field">
                    <label class="os-label">Sumber dana</label>
                    <select wire:model="form_sumber_dana_id" class="os-select" @disabled($editingId)>
                        <option value="">— pilih —</option>
                        @foreach ($sumberDana as $sumber)
                            <option value="{{ $sumber->id }}">{{ $sumber->nama }}</option>
                        @endforeach
                    </select>
                    @error('form_sumber_dana_id') <div class="os-error">{{ $message }}</div> @enderror
                </div>
                <div class="os-field">
                    <label class="os-label">Unit</label>
                    <select wire:model="form_facility_id" class="os-select" @disabled($editingId)>
                        <option value="0">Rumah Sakit (RS)</option>
                        @foreach ($facilities as $facility)
                            <option value="{{ $facility->id }}">{{ $facility->nama }}</option>
                        @endforeach
                    </select>
                    @error('form_facility_id') <div class="os-error">{{ $message }}</div> @enderror
                </div>
                <div class="os-field">
                    <label class="os-label">Tahun</label>
                    <input type="number" class="os-input" value="{{ $tahun }}" disabled>
                </div>
                <div class="os-field">
                    <label class="os-label">Jumlah pagu</label>
                    <input type="number" min="0" step="0.01" wire:model="jumlah_anggaran" class="os-input">
                    @error('jumlah_anggaran') <div class="os-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="os-floating os-section-tight">
                <input type="text" wire:model="keterangan" placeholder=" " class="os-floating-input">
                <label class="os-floating-label">Keterangan revisi (opsional)</label>
            </div>
            <div class="os-section os-toolbar">
                <button wire:click="save" class="os-btn os-btn--primary">
                    <x-icon name="save-line" :size="16" /> Simpan pagu
                </button>
                <button wire:click="$set('showForm', false)" class="os-btn os-btn--secondary">Batal</button>
            </div>
            @if ($editingId)
                <div class="os-help os-section-tight">Revisi menaikkan versi pagu. Riwayat versi sebelumnya tidak disimpan terpisah.</div>
            @endif
        </div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-table-wrap">
            <table class="os-table os-table--striped os-table--hover">
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
                                    <button wire:click="edit({{ $anggaran->id }})" class="os-btn os-btn--sm os-btn--secondary">Revisi</button>
                                    <button wire:click="delete({{ $anggaran->id }})"
                                            wire:confirm="Hapus pagu ini?"
                                            class="os-btn os-btn--sm os-btn--danger">Hapus</button>
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

    <div class="os-help os-section">
        Komitmen = total PO yang belum dibatalkan. Realisasi diakui saat penerimaan barang,
        sehingga nilai yang sudah diterima berpindah dari komitmen ke realisasi dan tidak dihitung dua kali.
        Untuk unit puskesmas, komitmen dan realisasi baru terhubung setelah modul distribusi tersedia.
    </div>
</div>
