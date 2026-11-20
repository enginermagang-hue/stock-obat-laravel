<div>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Purchase Order</h1>
            <p class="os-page-subtitle">Komitmen pembelian dan penerimaan barang.</p>
        </div>
        <button wire:click="$toggle('showForm')" class="os-btn os-btn--primary">
            <x-icon name="add-line" :size="16" /> Buat PO
        </button>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    @if ($showForm)
        <div class="os-section os-card os-card-body">
            <h2 class="os-card-title">PO baru</h2>
            <div class="os-section-tight os-form-grid">
                <div class="os-field">
                    <label class="os-label">Supplier</label>
                    <select wire:model="supplier_id" class="os-select">
                        <option value="">— pilih —</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="os-field">
                    <label class="os-label">Sumber dana</label>
                    <select wire:model="sumber_dana_id" class="os-select">
                        <option value="">— pilih —</option>
                        @foreach ($sumberDana as $s)
                            <option value="{{ $s->id }}">{{ $s->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="os-floating">
                    <input type="date" wire:model="tanggal_po" placeholder=" " class="os-floating-input">
                    <label class="os-floating-label">Tanggal PO</label>
                </div>
                <div class="os-floating">
                    <input type="text" wire:model="catatan" placeholder=" " class="os-floating-input">
                    <label class="os-floating-label">Catatan</label>
                </div>
            </div>

            <div class="os-table-wrap os-section-tight">
                <table class="os-table">
                    <thead><tr><th>Obat</th><th>Jumlah</th><th>Harga beli</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($items as $index => $item)
                        <tr>
                            <td>
                                <select wire:model="items.{{ $index }}.obat_id" class="os-select">
                                    <option value="">— pilih —</option>
                                    @foreach ($obat as $o)
                                        <option value="{{ $o->id }}">{{ $o->nama }} ({{ $o->kode_obat }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" min="1" wire:model="items.{{ $index }}.jumlah" class="os-input"></td>
                            <td><input type="number" min="0" wire:model="items.{{ $index }}.harga_beli" class="os-input"></td>
                            <td class="os-td-right">
                                <button wire:click="removeItem({{ $index }})" class="os-btn os-btn--sm os-btn--danger">
                                    <x-icon name="delete-bin-line" :size="14" />
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="os-section os-toolbar">
                <button wire:click="addItem" class="os-btn os-btn--secondary">
                    <x-icon name="add-line" :size="16" /> Tambah item
                </button>
                <button wire:click="save" class="os-btn os-btn--primary">
                    <x-icon name="save-line" :size="16" /> Simpan PO
                </button>
            </div>
        </div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-table-wrap">
            <table class="os-table os-table--striped os-table--hover">
                <thead><tr><th>No. PO</th><th>Supplier</th><th>Sumber dana</th><th>Tanggal</th><th class="os-td-right">Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ $order->no_po }}</td>
                        <td>{{ $order->supplier?->nama }}</td>
                        <td>{{ $order->sumberDana?->nama }}</td>
                        <td>{{ $order->tanggal_po?->format('d-m-Y') }}</td>
                        <td class="os-td-right">Rp {{ number_format((float) $order->total, 0, ',', '.') }}</td>
                        <td>
                            <span class="os-status-pill {{ $order->status->value === 'diterima' ? 'os-status-pill--success' : ($order->status->value === 'dibatalkan' ? 'os-status-pill--danger' : 'os-status-pill--process') }}">
                                {{ str_replace('_', ' ', $order->status->value) }}
                            </span>
                        </td>
                        <td class="os-td-right">
                            <div class="os-table-actions">
                                @if ($order->status === \App\Enums\StatusPurchaseOrder::Draft)
                                    <button wire:click="send({{ $order->id }})" class="os-btn os-btn--sm os-btn--secondary">Kirim</button>
                                @endif
                                @can('approve-po')
                                    @if ($order->status === \App\Enums\StatusPurchaseOrder::Draft)
                                        <button wire:click="approve({{ $order->id }})" class="os-btn os-btn--sm os-btn--primary">Approve</button>
                                    @endif
                                    @if (! in_array($order->status->value, ['diterima', 'dibatalkan']))
                                        <button wire:click="cancel({{ $order->id }})" class="os-btn os-btn--sm os-btn--danger">Batal</button>
                                    @endif
                                @endcan
                                @if (in_array($order->status->value, ['dikirim', 'diterima_sebagian']))
                                    <a href="{{ route('pembelian.penerimaan', ['po' => $order->id]) }}" class="os-btn os-btn--sm os-btn--primary">Terima</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="os-empty">Belum ada PO.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="os-pagination">{{ $orders->links() }}</div>
    </div>
</div>
