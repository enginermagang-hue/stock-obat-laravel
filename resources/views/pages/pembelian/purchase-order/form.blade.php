@extends('layouts.app')

@section('title', $title)

@section('content')
    @php
        $editing = $po !== null;
        $rows = old('items', $editing
            ? $po->items->map(fn ($item) => [
                'obat_id' => $item->obat_id,
                'jumlah' => $item->jumlah,
                'harga_beli' => (float) $item->harga_beli,
            ])->values()->all()
            : []);
    @endphp

    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">{{ $title }}</h1>
            <p class="os-page-subtitle">{{ $editing ? 'Perbaiki data PO lalu simpan.' : 'Susun PO baru untuk pengadaan barang.' }}</p>
        </div>
        <a href="{{ $editing ? route('pembelian.purchase-order.show', $po) : route('pembelian.purchase-order') }}"
           class="os-btn os-btn--secondary">
            <x-icon name="arrow-left-line" /> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="os-alert os-alert--danger os-section-tight">{{ $errors->first() }}</div>
    @endif

    <div x-data="poForm({
            rows: {{ Illuminate\Support\Js::from($rows) }},
            meta: {{ Illuminate\Support\Js::from($obatMeta) }},
        })">
        <div class="os-section os-card os-card-body">
            <form method="POST"
                  action="{{ $editing ? route('pembelian.purchase-order.update', $po) : route('pembelian.purchase-order.store') }}"
                  x-ref="form">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div class="os-section-tight os-form-grid">
                    <div class="os-field">
                        <label class="os-label" for="supplier_id">Supplier</label>
                        <select id="supplier_id" name="supplier_id" class="os-select">
                            <option value="">— pilih —</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $po?->supplier_id) == $supplier->id)>{{ $supplier->nama }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <span class="os-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="os-field">
                        <label class="os-label" for="sumber_dana_id">Sumber dana</label>
                        <select id="sumber_dana_id" name="sumber_dana_id" class="os-select">
                            <option value="">— pilih —</option>
                            @foreach ($sumberDana as $dana)
                                <option value="{{ $dana->id }}" @selected(old('sumber_dana_id', $po?->sumber_dana_id) == $dana->id)>{{ $dana->nama }}</option>
                            @endforeach
                        </select>
                        @error('sumber_dana_id') <span class="os-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="os-floating">
                        <input type="date" name="tanggal_po" placeholder=" "
                               value="{{ old('tanggal_po', $po?->tanggal_po?->toDateString() ?? now()->toDateString()) }}"
                               class="os-floating-input">
                        <label class="os-floating-label">Tanggal PO</label>
                    </div>
                    <div class="os-floating">
                        <input type="text" name="catatan" placeholder=" " value="{{ old('catatan', $po?->catatan) }}" class="os-floating-input">
                        <label class="os-floating-label">Catatan</label>
                    </div>
                </div>

                <div class="os-card-head os-section-tight">
                    <div>
                        <div class="os-card-title">Item PO</div>
                        <div class="os-card-subtitle" x-text="`${rows.length} item · total ${rupiah(grandTotal)}`"></div>
                    </div>
                    <button type="button" class="os-btn os-btn--secondary" @click="openAdd()">
                        <x-icon name="add-line" /> Tambah item
                    </button>
                </div>

                <div class="os-table-wrap">
                    <table class="os-table">
                        <thead>
                        <tr>
                            <th>Obat</th>
                            <th>Bentuk sediaan</th>
                            <th>Satuan</th>
                            <th class="os-td-right">Jumlah</th>
                            <th class="os-td-right">Harga beli</th>
                            <th class="os-td-right">Total</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <template x-for="(row, index) in rows" :key="row.obat_id">
                            <tr>
                                <td>
                                    <div class="font-medium text-heading" x-text="obatName(row.obat_id)"></div>
                                    <input type="hidden" :name="`items[${index}][obat_id]`" :value="row.obat_id">
                                </td>
                                <td x-text="meta[row.obat_id]?.bentuk ?? '—'"></td>
                                <td x-text="meta[row.obat_id]?.satuan ?? '—'"></td>
                                <td class="os-td-right">
                                    <span x-text="row.jumlah"></span>
                                    <input type="hidden" :name="`items[${index}][jumlah]`" :value="row.jumlah">
                                </td>
                                <td class="os-td-right">
                                    <span x-text="rupiah(row.harga_beli)"></span>
                                    <input type="hidden" :name="`items[${index}][harga_beli]`" :value="row.harga_beli">
                                </td>
                                <td class="os-td-right" x-text="rupiah(subtotal(row))"></td>
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
                            <td colspan="7" class="os-empty">Belum ada item. Klik "Tambah item" untuk mulai.</td>
                        </tr>
                        </tbody>
                        <tfoot x-show="rows.length > 0">
                        <tr>
                            <td colspan="5" class="os-td-right font-medium text-heading">Total PO</td>
                            <td class="os-td-right font-medium text-heading" x-text="rupiah(grandTotal)"></td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="os-section os-toolbar">
                    <button type="submit" class="os-btn os-btn--primary">
                        <x-icon name="save-line" /> {{ $editing ? 'Simpan Perubahan' : 'Simpan PO' }}
                    </button>
                </div>
            </form>
        </div>

        <x-modal show="itemModal" close="itemModal = false" size="lg">
            <x-slot:title>
                <h2 class="os-card-title" x-text="itemIndex === null ? 'Tambah item' : 'Ubah item'">Tambah item</h2>
                <p class="os-card-subtitle">Pilih obat lalu tentukan jumlah dan harga beli.</p>
            </x-slot:title>

            <div class="os-section-tight">
                <div class="os-field os-floating">
                    <select x-model="draft.obat_id" @change="onObatChange()" x-ref="obatSelect"
                            data-dropdown="body" class="os-floating-input os-select">
                        <option value="">— pilih obat —</option>
                        @foreach ($obat as $item)
                            <option value="{{ $item->id }}">{{ $item->nama }} ({{ $item->kode_obat }})</option>
                        @endforeach
                    </select>
                    <label class="os-floating-label">Obat</label>
                </div>
                <div class="os-floating os-section-tight">
                    <input type="number" min="1" x-model.number="draft.jumlah" placeholder=" " class="os-floating-input">
                    <label class="os-floating-label">Jumlah</label>
                </div>
                <div class="os-floating os-section-tight">
                    <input type="number" min="0" step="0.01" x-model.number="draft.harga_beli" placeholder=" " class="os-floating-input">
                    <label class="os-floating-label">Harga beli</label>
                </div>
                <div class="os-field os-section-tight">
                    <span class="os-label">Subtotal</span>
                    <div class="font-medium text-heading" x-text="rupiah(draftSubtotal)"></div>
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
