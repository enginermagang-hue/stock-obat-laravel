<div>
    <h1 class="os-page-title">Penerimaan Barang</h1>
    <p class="os-page-subtitle">Terima PO → buat batch → catat ledger masuk.</p>

    <div class="os-section os-card os-card-body">
        <div class="os-form-grid">
            <div class="os-field">
                <label class="os-label">Purchase Order</label>
                <select wire:model.live="po_id" class="os-select">
                    <option value="">— pilih PO —</option>
                    @foreach ($orders as $order)
                        <option value="{{ $order->id }}">{{ $order->no_po }} · {{ $order->supplier?->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="os-floating">
                <input type="date" wire:model="tanggal_terima" placeholder=" " class="os-floating-input">
                <label class="os-floating-label">Tanggal terima</label>
            </div>
        </div>

        @if (count($items))
            <div class="os-table-wrap os-section-tight">
                <table class="os-table">
                    <thead>
                    <tr><th>Obat</th><th class="os-td-right">Sisa PO</th><th>No. batch</th><th>Expiry</th><th class="os-td-right">Jumlah</th><th class="os-td-right">Harga</th><th>Rak</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($items as $index => $item)
                        <tr>
                            <td>{{ $item['nama'] }}</td>
                            <td class="os-td-right">{{ $item['sisa'] }}</td>
                            <td><input type="text" wire:model="items.{{ $index }}.no_batch" class="os-input"></td>
                            <td><input type="date" wire:model="items.{{ $index }}.tanggal_kedaluwarsa" class="os-input"></td>
                            <td><input type="number" min="1" max="{{ $item['sisa'] }}" wire:model="items.{{ $index }}.jumlah" class="os-input"></td>
                            <td><input type="number" min="0" wire:model="items.{{ $index }}.harga_beli" class="os-input"></td>
                            <td><input type="text" wire:model="items.{{ $index }}.lokasi_rak" class="os-input"></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="os-floating os-section-tight">
                <input type="text" wire:model="catatan" placeholder=" " class="os-floating-input">
                <label class="os-floating-label">Catatan</label>
            </div>
            <div class="os-section">
                <button wire:click="save" class="os-btn os-btn--primary">
                    <x-icon name="save-line" :size="16" /> Simpan Penerimaan
                </button>
            </div>
        @elseif ($po_id)
            <div class="os-alert os-alert--warning os-section-tight">PO tidak memiliki sisa item yang dapat diterima.</div>
        @endif
    </div>
</div>
