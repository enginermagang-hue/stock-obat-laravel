<div>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">LPLPO</h1>
            <p class="os-page-subtitle">Laporan pemakaian dan stok obat per puskesmas.</p>
        </div>
        <button wire:click="$toggle('showForm')" class="os-btn os-btn--primary">
            <x-icon name="add-line" :size="16" /> Buat LPLPO
        </button>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    @if ($showForm)
        <div class="os-section os-card os-card-body">
            <h2 class="os-card-title">LPLPO baru</h2>
            <div class="os-section-tight os-form-grid">
                @unless (auth()->user()->facility_id)
                    <div class="os-field">
                        <label class="os-label">Puskesmas</label>
                        <select wire:model="facility_id" class="os-select">
                            <option value="">— pilih —</option>
                            @foreach ($facilities as $facility)
                                <option value="{{ $facility->id }}">{{ $facility->kode }} · {{ $facility->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless
                <div class="os-field">
                    <label class="os-label">Bulan</label>
                    <input type="number" min="1" max="12" wire:model="bulan" class="os-input">
                </div>
                <div class="os-field">
                    <label class="os-label">Tahun</label>
                    <input type="number" wire:model="tahun" class="os-input">
                </div>
                <div class="os-floating">
                    <input type="text" wire:model="catatan" placeholder=" " class="os-floating-input">
                    <label class="os-floating-label">Catatan</label>
                </div>
            </div>

            <div class="os-table-wrap os-section-tight">
                <table class="os-table">
                    <thead><tr><th>Obat</th><th>Pemasukan</th><th>Pemakaian</th><th>Stok akhir</th><th>Diminta</th><th></th></tr></thead>
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
                            <td><input type="number" min="0" wire:model="items.{{ $index }}.pemasukan" class="os-input"></td>
                            <td><input type="number" min="0" wire:model="items.{{ $index }}.pemakaian" class="os-input"></td>
                            <td><input type="number" min="0" wire:model="items.{{ $index }}.stok_akhir" class="os-input"></td>
                            <td><input type="number" min="0" wire:model="items.{{ $index }}.jumlah_diminta" class="os-input"></td>
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
                <button wire:click="addItem" class="os-btn os-btn--secondary"><x-icon name="add-line" :size="16" /> Tambah item</button>
                <button wire:click="save" class="os-btn os-btn--primary"><x-icon name="save-line" :size="16" /> Simpan Draft</button>
            </div>
            <div class="os-help os-section-tight">Stok awal diambil otomatis dari LPLPO terverifikasi periode sebelumnya.</div>
        </div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-table-wrap">
            <table class="os-table os-table--striped os-table--hover">
                <thead><tr><th>No. LPLPO</th><th>Puskesmas</th><th>Periode</th><th>Versi</th><th class="os-td-right">Item</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($lplpos as $lplpo)
                    <tr>
                        <td>{{ $lplpo->no_lplpo }}</td>
                        <td>{{ $lplpo->facility?->nama }}</td>
                        <td>{{ $lplpo->bulan }} / {{ $lplpo->tahun }}</td>
                        <td>{{ $lplpo->versi }}</td>
                        <td class="os-td-right">{{ $lplpo->items->count() }}</td>
                        <td>
                            <span class="os-status-pill {{ $lplpo->status === \App\Enums\StatusLplpo::Diverifikasi ? 'os-status-pill--success' : ($lplpo->status === \App\Enums\StatusLplpo::Ditolak ? 'os-status-pill--danger' : 'os-status-pill--process') }}">
                                {{ $lplpo->status->value }}
                            </span>
                        </td>
                        <td class="os-td-right"><button wire:click="select({{ $lplpo->id }})" class="os-btn os-btn--sm os-btn--secondary">Buka</button></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="os-empty">Belum ada LPLPO.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="os-pagination">{{ $lplpos->links() }}</div>
    </div>

    @if ($selected)
        <div class="os-section os-card os-card--flush">
            <div class="os-card-head">
                <div>
                    <div class="os-card-title">{{ $selected->no_lplpo }}</div>
                    <div class="os-card-subtitle">
                        {{ $selected->facility?->nama }} · {{ $selected->bulan }}/{{ $selected->tahun }} · versi {{ $selected->versi }}
                    </div>
                </div>
                <div class="os-toolbar">
                    @if ($selected->status === \App\Enums\StatusLplpo::Draft)
                        <button wire:click="submit({{ $selected->id }})" class="os-btn os-btn--primary">
                            <x-icon name="send-plane-line" :size="16" /> Kirim
                        </button>
                    @endif
                    @can('verify-lplpo')
                        @if ($selected->status === \App\Enums\StatusLplpo::Dikirim)
                            <button wire:click="verify({{ $selected->id }}, true)" class="os-btn os-btn--primary">
                                <x-icon name="check-line" :size="16" /> Verifikasi
                            </button>
                            <button wire:click="verify({{ $selected->id }}, false)" class="os-btn os-btn--danger">
                                <x-icon name="close-line" :size="16" /> Tolak
                            </button>
                        @endif
                    @endcan
                    @can('create-permintaan')
                        @if ($selected->status === \App\Enums\StatusLplpo::Diverifikasi)
                            <button wire:click="createPermintaan({{ $selected->id }})" class="os-btn os-btn--primary">
                                <x-icon name="send-plane-line" :size="16" /> Buat Permintaan
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
            <div class="os-table-wrap">
                <table class="os-table">
                    <thead>
                    <tr><th>Obat</th><th class="os-td-right">Stok awal</th><th class="os-td-right">Pemasukan</th><th class="os-td-right">Pemakaian</th><th class="os-td-right">Stok akhir</th><th class="os-td-right">Sistem</th><th class="os-td-right">Diminta</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($selected->items as $item)
                        <tr>
                            <td>{{ $item->obat?->nama }}</td>
                            <td class="os-td-right">{{ $item->stok_awal }}</td>
                            <td class="os-td-right">{{ $item->pemasukan }}</td>
                            <td class="os-td-right">{{ $item->pemakaian }}</td>
                            <td class="os-td-right">{{ $item->stok_akhir }}</td>
                            <td class="os-td-right {{ $item->stok_akhir !== $item->stok_akhir_sistem ? 'text-danger' : '' }}">{{ $item->stok_akhir_sistem }}</td>
                            <td class="os-td-right">{{ $item->jumlah_diminta }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
