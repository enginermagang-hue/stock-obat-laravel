<div>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Permintaan Obat</h1>
            <p class="os-page-subtitle">Permintaan dari LPLPO atau mandiri, diverifikasi apoteker per item.</p>
        </div>
        <button wire:click="$toggle('showForm')" class="os-btn os-btn--primary">
            <x-icon name="add-line" :size="16" /> Permintaan Mandiri
        </button>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    @if ($showForm)
        <div class="os-section os-card os-card-body">
            <h2 class="os-card-title">Permintaan mandiri</h2>
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
                <div class="os-floating">
                    <input type="date" wire:model="periode_awal" placeholder=" " class="os-floating-input">
                    <label class="os-floating-label">Periode awal</label>
                </div>
                <div class="os-floating">
                    <input type="date" wire:model="periode_akhir" placeholder=" " class="os-floating-input">
                    <label class="os-floating-label">Periode akhir</label>
                </div>
            </div>

            <div class="os-table-wrap os-section-tight">
                <table class="os-table">
                    <thead><tr><th>Obat</th><th>Jumlah diminta</th><th>Keterangan</th><th></th></tr></thead>
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
                            <td><input type="number" min="1" wire:model="items.{{ $index }}.jumlah_diminta" class="os-input"></td>
                            <td><input type="text" wire:model="items.{{ $index }}.keterangan" class="os-input"></td>
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
        </div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-table-wrap">
            <table class="os-table os-table--striped os-table--hover">
                <thead><tr><th>No. Permintaan</th><th>Puskesmas</th><th>Sumber</th><th>Periode</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($permintaans as $permintaan)
                    <tr>
                        <td>{{ $permintaan->no_permintaan }}</td>
                        <td>{{ $permintaan->facility?->nama }}</td>
                        <td><span class="os-badge">{{ $permintaan->sumber->value }}</span></td>
                        <td>{{ $permintaan->periode_awal?->format('d-m-Y') }} – {{ $permintaan->periode_akhir?->format('d-m-Y') }}</td>
                        <td>
                            <span class="os-status-pill {{ in_array($permintaan->status->value, ['disetujui', 'selesai']) ? 'os-status-pill--success' : (in_array($permintaan->status->value, ['ditolak', 'dibatalkan']) ? 'os-status-pill--danger' : 'os-status-pill--process') }}">
                                {{ $permintaan->status->value }}
                            </span>
                        </td>
                        <td class="os-td-right"><button wire:click="select({{ $permintaan->id }})" class="os-btn os-btn--sm os-btn--secondary">Buka</button></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="os-empty">Belum ada permintaan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="os-pagination">{{ $permintaans->links() }}</div>
    </div>

    @if ($selected)
        <div class="os-section os-card os-card--flush">
            <div class="os-card-head">
                <div>
                    <div class="os-card-title">{{ $selected->no_permintaan }}</div>
                    <div class="os-card-subtitle">{{ $selected->facility?->nama }} · sumber {{ $selected->sumber->value }} · status {{ $selected->status->value }}</div>
                </div>
                <div class="os-toolbar">
                    @if ($selected->status === \App\Enums\StatusPermintaan::Draft)
                        <button wire:click="submit({{ $selected->id }})" class="os-btn os-btn--primary">
                            <x-icon name="send-plane-line" :size="16" /> Kirim
                        </button>
                    @endif
                </div>
            </div>

            <div class="os-table-wrap">
                <table class="os-table">
                    <thead>
                    <tr><th>Obat</th><th class="os-td-right">Diminta</th><th class="os-td-right">Disetujui</th><th>Status item</th><th>Keterangan</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($selected->items as $item)
                        <tr>
                            <td>{{ $item->obat?->nama }}</td>
                            <td class="os-td-right">{{ $item->jumlah_diminta }}</td>
                            <td class="os-td-right">
                                @can('verify-permintaan')
                                    @if (in_array($selected->status->value, ['dikirim', 'diterima'], true))
                                        <input type="number" min="0" max="{{ $item->jumlah_diminta }}"
                                               wire:model="decisions.{{ $item->id }}.jumlah_disetujui" class="os-input os-td-right" style="width:100px">
                                    @else
                                        {{ $item->jumlah_disetujui ?? '—' }}
                                    @endif
                                @else
                                    {{ $item->jumlah_disetujui ?? '—' }}
                                @endcan
                            </td>
                            <td>
                                @if ($item->status_item)
                                    <span class="os-status-pill {{ in_array($item->status_item->value, ['disetujui', 'selesai']) ? 'os-status-pill--success' : ($item->status_item->value === 'ditolak' ? 'os-status-pill--danger' : 'os-status-pill--warning') }}">
                                        {{ $item->status_item->value }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $item->keterangan ?: '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @can('verify-permintaan')
                @if (in_array($selected->status->value, ['dikirim', 'diterima'], true))
                    <div class="os-card-body">
                        <div class="os-floating">
                            <input type="text" wire:model="catatan_verifikasi" placeholder=" " class="os-floating-input">
                            <label class="os-floating-label">Catatan verifikasi</label>
                        </div>
                        <div class="os-section">
                            <button wire:click="verify({{ $selected->id }})" class="os-btn os-btn--primary">
                                <x-icon name="check-line" :size="16" /> Simpan Verifikasi
                            </button>
                        </div>
                    </div>
                @endif
            @endcan
        </div>
    @endif
</div>
