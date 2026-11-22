@extends('layouts.app')

@section('title', 'Penyesuaian Stok')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Penyesuaian Stok</h1>
            <p class="os-page-subtitle">Koreksi stok karena expired, rusak, hilang, atau retur.</p>
        </div>
        <button type="button" class="os-btn os-btn--primary" x-data @click="$dispatch('open-adjustment')">
            <x-icon name="add-line" /> Buat Penyesuaian
        </button>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    <div x-data='{
            open: false,
            rows: [{ batch_id: "", jumlah: 0, alasan: "" }],
            add() { this.rows.push({ batch_id: "", jumlah: 0, alasan: "" }); },
            remove(index) { this.rows.splice(index, 1); if (this.rows.length === 0) this.add(); },
        }'
         @open-adjustment.window="open = true" x-show="open" x-cloak
         class="os-section os-card os-card-body">
        <h2 class="os-card-title">Penyesuaian baru</h2>
        <form method="POST" action="{{ route('stok.adjustment.store') }}">
            @csrf
            <div class="os-section-tight os-form-grid">
                <div class="os-field">
                    <label class="os-label" for="tipe">Tipe</label>
                    <select id="tipe" name="tipe" class="os-select">
                        @foreach ($tipeOptions as $tipe)
                            <option value="{{ $tipe->value }}" @selected(old('tipe') === $tipe->value)>{{ $tipe->value }}</option>
                        @endforeach
                    </select>
                    @error('tipe') <span class="os-error">{{ $message }}</span> @enderror
                </div>
                <div class="os-field">
                    <label class="os-label" for="tanggal">Tanggal</label>
                    <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" class="os-input">
                    @error('tanggal') <span class="os-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="os-floating os-section-tight">
                <input type="text" name="catatan" placeholder=" " value="{{ old('catatan') }}" class="os-floating-input">
                <label class="os-floating-label">Catatan</label>
            </div>

            <div class="os-section-tight">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="os-form-grid os-section-tight">
                        <div class="os-field">
                            <label class="os-label">Batch obat</label>
                            <select :name="`items[${index}][batch_id]`" x-model="row.batch_id" class="os-select">
                                <option value="">— pilih batch —</option>
                                @foreach ($batches as $batch)
                                    <option value="{{ $batch->id }}">
                                        {{ $batch->obat?->nama }} · {{ $batch->no_batch }} · sisa {{ $batch->jumlah_sisa }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="os-field">
                            <label class="os-label">Jumlah (+/-)</label>
                            <input type="number" :name="`items[${index}][jumlah]`" x-model="row.jumlah" class="os-input">
                        </div>
                        <div class="os-field">
                            <label class="os-label">Alasan</label>
                            <input type="text" :name="`items[${index}][alasan]`" x-model="row.alasan" class="os-input">
                        </div>
                        <div class="os-field">
                            <label class="os-label">&nbsp;</label>
                            <button type="button" class="os-btn os-btn--sm os-btn--danger" @click="remove(index)">
                                <x-icon name="delete-bin-line" /> Hapus baris
                            </button>
                        </div>
                    </div>
                </template>
                <button type="button" class="os-btn os-btn--secondary os-section-tight" @click="add()">
                    <x-icon name="add-line" /> Tambah item
                </button>
            </div>

            <div class="os-section os-toolbar">
                <button type="submit" class="os-btn os-btn--primary">
                    <x-icon name="save-line" /> Simpan
                </button>
                <button type="button" class="os-btn os-btn--secondary" @click="open = false">
                    <x-icon name="close-line" /> Batal
                </button>
            </div>
        </form>
    </div>

    <div class="os-section os-card os-card--flush">
        <div class="os-table-wrap">
            <table class="os-table os-table--hover">
                <thead>
                <tr>
                    <th>No. Penyesuaian</th><th>Tanggal</th><th>Tipe</th><th class="os-td-right">Item</th><th>Status</th><th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($adjustments as $adjustment)
                    <tr>
                        <td>{{ $adjustment->no_penyesuaian }}</td>
                        <td>{{ $adjustment->tanggal?->format('d-m-Y') }}</td>
                        <td><span class="os-badge">{{ $adjustment->tipe->value }}</span></td>
                        <td class="os-td-right">{{ $adjustment->items_count }}</td>
                        <td>
                            <span class="os-status-pill {{ $adjustment->status->value === 'diproses' ? 'os-status-pill--success' : ($adjustment->status->value === 'dibatalkan' ? 'os-status-pill--danger' : 'os-status-pill--process') }}">
                                {{ $adjustment->status->value }}
                            </span>
                        </td>
                        <td class="os-td-right">
                            @can('approve-po')
                                @if ($adjustment->status === \App\Enums\StatusPenyesuaian::Draft)
                                    <div class="os-table-actions">
                                        <form method="POST" action="{{ route('stok.adjustment.process', $adjustment) }}">
                                            @csrf
                                            <button type="submit" class="os-btn os-btn--sm os-btn--primary">Proses</button>
                                        </form>
                                        <form method="POST" action="{{ route('stok.adjustment.cancel', $adjustment) }}">
                                            @csrf
                                            <button type="submit" class="os-btn os-btn--sm os-btn--danger">Batal</button>
                                        </form>
                                    </div>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="os-empty">Belum ada penyesuaian.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="os-pagination">{{ $adjustments->links() }}</div>
    </div>
@endsection
