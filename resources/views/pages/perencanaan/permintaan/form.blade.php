@extends('layouts.app')

@section('title', $title)

@section('content')
    @php
        $editing = $permintaan !== null;
        $rows = old('items', $editing
            ? $permintaan->items->map(fn ($item) => [
                'obat_id' => $item->obat_id,
                'jumlah_diminta' => $item->jumlah_diminta,
                'keterangan' => $item->keterangan,
            ])->values()->all()
            : []);
    @endphp

    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">{{ $title }}</h1>
            <p class="os-page-subtitle">
                {{ $editing ? 'Perbaiki item permintaan lalu simpan.' : 'Susun permintaan mandiri untuk periode tertentu.' }}
            </p>
        </div>
        <a href="{{ $editing ? route('permintaan.show', $permintaan) : route('permintaan.index') }}" class="os-btn os-btn--secondary">
            <x-icon name="arrow-left-line" /> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="os-alert os-alert--danger os-section-tight">{{ $errors->first() }}</div>
    @endif

    <div data-permintaan-form x-data="permintaanForm({
            rows: {{ Illuminate\Support\Js::from($rows) }},
            names: {{ Illuminate\Support\Js::from($obatNames) }},
        })">
        <div class="os-section os-card os-card-body">
            <form method="POST"
                  action="{{ $editing ? route('permintaan.update', $permintaan) : route('permintaan.store') }}"
                  x-ref="form">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div class="os-section-tight os-form-grid">
                    @unless (auth()->user()->facility_id)
                        <div class="os-floating">
                            <select id="facility_id" name="facility_id" class="os-floating-input os-select">
                                <option value="">— pilih —</option>
                                @foreach ($facilities as $facility)
                                    <option value="{{ $facility->id }}" @selected(old('facility_id', $permintaan?->facility_id) == $facility->id)>{{ $facility->kode }} · {{ $facility->nama }}</option>
                                @endforeach
                            </select>
                            <label class="os-floating-label" for="facility_id">Puskesmas</label>
                            @error('facility_id') <span class="os-error">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <input type="hidden" name="facility_id" value="{{ auth()->user()->facility_id }}">
                    @endunless
                    <div class="os-floating">
                        <input id="periode_awal" type="date" name="periode_awal" placeholder=" "
                               value="{{ old('periode_awal', $permintaan?->periode_awal?->toDateString() ?? now()->startOfMonth()->toDateString()) }}"
                               class="os-floating-input">
                        <label class="os-floating-label" for="periode_awal">Periode awal</label>
                        @error('periode_awal') <span class="os-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="os-floating">
                        <input id="periode_akhir" type="date" name="periode_akhir" placeholder=" "
                               value="{{ old('periode_akhir', $permintaan?->periode_akhir?->toDateString() ?? now()->endOfMonth()->toDateString()) }}"
                               class="os-floating-input">
                        <label class="os-floating-label" for="periode_akhir">Periode akhir</label>
                        @error('periode_akhir') <span class="os-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="os-card-head os-section-tight">
                    <div>
                        <div class="os-card-title">Item permintaan</div>
                        <div class="os-card-subtitle" x-text="`${rows.length} item`"></div>
                    </div>
                    <button type="button" class="os-btn os-btn--secondary" @click="openAdd()">
                        <x-icon name="add-line" /> Tambah item
                    </button>
                </div>

                <div class="os-table-wrap">
                    <table class="os-table">
                        <thead><tr><th>Obat</th><th class="os-td-right">Jumlah diminta</th><th>Keterangan</th><th></th></tr></thead>
                        <tbody>
                        <template x-for="(row, index) in rows" :key="row.obat_id">
                            <tr>
                                <td>
                                    <div class="font-medium text-heading" x-text="obatName(row.obat_id)"></div>
                                    <input type="hidden" :name="`items[${index}][obat_id]`" :value="row.obat_id">
                                </td>
                                <td class="os-td-right">
                                    <span x-text="row.jumlah_diminta"></span>
                                    <input type="hidden" :name="`items[${index}][jumlah_diminta]`" :value="row.jumlah_diminta">
                                </td>
                                <td>
                                    <span x-text="row.keterangan || '—'"></span>
                                    <input type="hidden" :name="`items[${index}][keterangan]`" :value="row.keterangan ?? ''">
                                </td>
                                <td class="os-td-right">
                                    <div class="os-table-actions">
                                        <button type="button" class="os-btn os-btn--sm os-btn--secondary" @click="openEdit(index)">
                                            <x-icon name="edit-line" />
                                        </button>
                                        <button type="button" class="os-btn os-btn--sm os-btn--danger" @click="remove(index)">
                                            <x-icon name="delete-bin-line" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="rows.length === 0">
                            <td colspan="4" class="os-empty">Belum ada item. Klik "Tambah item" untuk mulai.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="os-section os-toolbar">
                    <button type="submit" class="os-btn os-btn--primary">
                        <x-icon name="save-line" /> {{ $editing ? 'Simpan Perubahan' : 'Simpan Draft' }}
                    </button>
                </div>
            </form>
        </div>

        <x-modal show="itemModal" close="itemModal = false">
            <x-slot:title>
                <h2 class="os-card-title" x-text="itemIndex === null ? 'Tambah item' : 'Ubah item'">Tambah item</h2>
                <p class="os-card-subtitle">Pilih obat lalu isi jumlah yang diminta.</p>
            </x-slot:title>

            <div class="os-section-tight">
                <div class="os-field os-floating">
                    <select x-model="draft.obat_id" data-dropdown="body" class="os-floating-input os-select">
                        <option value="">— pilih obat —</option>
                        @foreach ($obat as $item)
                            <option value="{{ $item->id }}">{{ $item->nama }} ({{ $item->kode_obat }})</option>
                        @endforeach
                    </select>
                    <label class="os-floating-label">Obat</label>
                </div>

                <div class="os-floating os-section-tight">
                    <input type="number" min="1" x-model.number="draft.jumlah_diminta" placeholder=" " class="os-floating-input">
                    <label class="os-floating-label">Jumlah diminta</label>
                </div>

                <div class="os-floating os-section-tight">
                    <input type="text" x-model="draft.keterangan" placeholder=" " class="os-floating-input">
                    <label class="os-floating-label">Keterangan (opsional)</label>
                </div>
            </div>

            <p class="os-error os-section-tight" x-show="itemError" x-text="itemError"></p>

            <x-slot:footer>
                <button type="button" class="os-btn os-btn--secondary" @click="itemModal = false">
                    <x-icon name="close-line" /> Batal
                </button>
                <button type="button" class="os-btn os-btn--primary" @click="saveItem()">
                    <x-icon name="check-line" /> <span x-text="itemIndex === null ? 'Tambah ke tabel' : 'Simpan item'"></span>
                </button>
            </x-slot:footer>
        </x-modal>
    </div>
@endsection
