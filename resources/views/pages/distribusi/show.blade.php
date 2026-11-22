@extends('layouts.app')

@section('title', $title)

@section('content')
    @php
        $status = $distribution->status;
        $editable = $status === \App\Enums\StatusDistribusi::Draft;
        $flow = ['draft', 'disetujui', 'dikirim', 'diterima'];
        $statusBadge = [
            'draft' => 'os-status-pill--draft',
            'disetujui' => 'os-status-pill--success',
            'dikirim' => 'os-status-pill--process',
            'diterima' => 'os-status-pill--success',
            'dibatalkan' => 'os-status-pill--danger',
        ];
    @endphp

    <div x-data="confirmModal({
            me: {
                nama: '{{ auth()->user()->name }}',
                nip: '{{ auth()->user()->nip }}',
                jabatan: '{{ auth()->user()->jabatan }}',
                unit: '{{ auth()->user()->facility?->nama }}',
            },
            defaults: {
                nama: '{{ old('penerima_nama') }}',
                nip: '{{ old('penerima_nip') }}',
                jabatan: '{{ old('penerima_jabatan') }}',
                unit: '{{ old('penerima_unit') }}',
            },
        })">
        <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">{{ $distribution->no_surat }}</h1>
            <p class="os-page-subtitle">
                {{ $distribution->facility?->nama }}
                @if ($distribution->permintaan)
                    · {{ $distribution->permintaan->no_permintaan }}
                @endif
                · status
                <span class="os-status-pill {{ $statusBadge[$status->value] ?? 'os-status-pill--draft' }}">{{ $status->value }}</span>
            </p>
        </div>
        <div class="os-toolbar">
            <a href="{{ route('distribusi.index') }}" class="os-btn os-btn--secondary">
                <x-icon name="arrow-left-line" /> Kembali
            </a>
            <div class="relative" x-data="{ open: false }">
                <button type="button" class="os-btn os-btn--secondary" @click="open = !open" :aria-expanded="open">
                    <x-icon name="file-download-line" /> Dokumen
                </button>
                <div x-show="open" x-cloak @click.outside="open = false"
                     class="os-dropdown absolute right-0 top-11 z-40 w-48">
                    <a href="{{ route('distribusi.surat-jalan', ['distribution' => $distribution, 'dokumen' => 'semua']) }}"
                       target="_blank" rel="noopener" class="os-dropdown-item">Semua Dokumen</a>
                    <a href="{{ route('distribusi.surat-jalan', ['distribution' => $distribution, 'dokumen' => 'surat-jalan']) }}"
                       target="_blank" rel="noopener" class="os-dropdown-item">Surat Jalan</a>
                    <a href="{{ route('distribusi.surat-jalan', ['distribution' => $distribution, 'dokumen' => 'sbbk']) }}"
                       target="_blank" rel="noopener" class="os-dropdown-item">SBBK</a>
                    <a href="{{ route('distribusi.surat-jalan', ['distribution' => $distribution, 'dokumen' => 'bast']) }}"
                       target="_blank" rel="noopener" class="os-dropdown-item">BAST</a>
                    @if ($distribution->file_tanda_terima)
                        <a href="{{ route('distribusi.tanda-terima', $distribution) }}" target="_blank" rel="noopener"
                           class="os-dropdown-item">Lihat Tanda Terima</a>
                    @endif
                </div>
            </div>
            @can('create-distribution')
                @if ($editable)
                    <a href="{{ route('distribusi.edit', $distribution) }}" class="os-btn os-btn--secondary">
                        <x-icon name="edit-line" /> Ubah
                    </a>
                @endif
            @endcan
            @can('confirm-distribution')
                @if ($status === \App\Enums\StatusDistribusi::Dikirim)
                    <button type="button" class="os-btn os-btn--primary" @click="openConfirm()">
                        <x-icon name="checkbox-circle-line" /> Konfirmasi Terima
                    </button>
                @endif
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="os-alert os-alert--danger os-section-tight">{{ $errors->first() }}</div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-card-head">
            <div>
                <div class="os-card-title">Informasi surat jalan</div>
                <div class="os-card-subtitle">{{ $distribution->items->count() }} item · total Rp {{ number_format($distribution->total(), 0, ',', '.') }}</div>
            </div>
            <div class="os-toolbar">
                @can('approve-distribution')
                    @if ($status === \App\Enums\StatusDistribusi::Draft)
                        <form method="POST" action="{{ route('distribusi.approve', $distribution) }}">
                            @csrf
                            <button type="submit" class="os-btn os-btn--sm os-btn--primary">
                                <x-icon name="check-line" /> Approve
                            </button>
                        </form>
                    @endif
                @endcan
                @can('create-distribution')
                    @if ($status === \App\Enums\StatusDistribusi::Disetujui)
                        <form method="POST" action="{{ route('distribusi.send', $distribution) }}">
                            @csrf
                            <button type="submit" class="os-btn os-btn--sm os-btn--primary">
                                <x-icon name="send-plane-line" /> Kirim
                            </button>
                        </form>
                    @endif
                @endcan
                @can('approve-distribution')
                    @if (in_array($status, [\App\Enums\StatusDistribusi::Draft, \App\Enums\StatusDistribusi::Disetujui], true))
                        <form method="POST" action="{{ route('distribusi.cancel', $distribution) }}"
                              onsubmit="return confirm('Batalkan surat jalan ini?')">
                            @csrf
                            <button type="submit" class="os-btn os-btn--sm os-btn--danger">
                                <x-icon name="close-line" /> Batalkan
                            </button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

        <div class="os-card-body os-form-grid">
            <div class="os-field">
                <span class="os-label">Puskesmas tujuan</span>
                <div>{{ $distribution->facility?->nama ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Permintaan</span>
                <div>
                    @if ($distribution->permintaan)
                        <a href="{{ route('permintaan.show', $distribution->permintaan) }}" class="font-medium underline">{{ $distribution->permintaan->no_permintaan }}</a>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="os-field">
                <span class="os-label">Tanggal surat</span>
                <div>{{ $distribution->tanggal?->format('d-m-Y') ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">No. SBBK</span>
                <div>{{ $distribution->no_sbbk ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Tanggal keluar</span>
                <div>{{ $distribution->tanggal_keluar?->format('d-m-Y') ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Catatan</span>
                <div>{{ $distribution->catatan ?: '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Dikirim oleh</span>
                <div>{{ $distribution->pengirim?->name ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Disetujui oleh</span>
                <div>{{ $distribution->approver?->name ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Penerima</span>
                <div>{{ $distribution->penerima_nama ?: '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">NIP penerima</span>
                <div>{{ $distribution->penerima_nip ?: '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Jabatan penerima</span>
                <div>{{ $distribution->penerima_jabatan ?: '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Unit kerja penerima</span>
                <div>{{ $distribution->penerima_unit ?: '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">No. BAST</span>
                <div>{{ $distribution->no_bast ?? '—' }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Tanggal diterima</span>
                <div>{{ $distribution->tanggal_diterima?->format('d-m-Y') ?? '—' }}</div>
            </div>
        </div>

        <div class="os-card-body">
            <div class="os-toolbar">
                @foreach ($flow as $step)
                    @php
                        $currentIndex = array_search($status->value, $flow, true);
                        $stepIndex = array_search($step, $flow, true);
                        $done = $currentIndex !== false && $stepIndex <= $currentIndex;
                    @endphp
                    <span class="os-status-pill {{ $done ? 'os-status-pill--success' : 'os-status-pill--draft' }}">{{ $step }}</span>
                    @unless ($loop->last)
                        <x-icon name="arrow-right-line" />
                    @endunless
                @endforeach
            </div>
        </div>
    </div>

    <div class="os-section os-card os-card--flush">
        <div class="os-card-head">
            <div>
                <div class="os-card-title">Item surat jalan</div>
                <div class="os-card-subtitle">Alokasi batch dengan FEFO</div>
            </div>
        </div>
        <div class="os-table-wrap">
            <table class="os-table">
                <thead>
                <tr>
                    <th>Obat</th>
                    <th>Bentuk / Kekuatan</th>
                    <th>Batch (FEFO)</th>
                    <th>Expiry</th>
                    <th class="os-td-right">Jumlah</th>
                    <th class="os-td-right">Harga satuan</th>
                    <th class="os-td-right">Subtotal</th>
                    <th class="os-td-right">Terpenuhi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($distribution->items as $item)
                    <tr>
                        <td>
                            <div class="font-medium text-heading">{{ $item->obat?->nama }}</div>
                            <div class="text-[13px] text-faint">{{ $item->obat?->kode_obat }}</div>
                        </td>
                        <td>
                            {{ $item->obat?->bentuk_sediaan?->value ?? '—' }}
                            @if ($item->obat?->kekuatan)
                                · {{ $item->obat->kekuatan }}
                            @endif
                        </td>
                        <td>{{ $item->batch?->no_batch ?? '—' }}</td>
                        <td>{{ $item->batch?->tanggal_kedaluwarsa?->format('d-m-Y') ?? '—' }}</td>
                        <td class="os-td-right">{{ $item->jumlah }} {{ $item->obat?->satuan }}</td>
                        <td class="os-td-right">Rp {{ number_format((float) $item->harga_satuan, 0, ',', '.') }}</td>
                        <td class="os-td-right">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                        <td class="os-td-right">
                            @if ($item->permintaanItem)
                                {{ $item->jumlah }} / {{ $item->permintaanItem->jumlah_disetujui ?? $item->permintaanItem->jumlah_diminta }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="os-empty">Belum ada item surat jalan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('confirm-distribution')
        @if ($status === \App\Enums\StatusDistribusi::Dikirim)
            <x-modal show="confirmModal" close="confirmModal = false">
                <x-slot:title>
                    <h2 class="os-card-title">Konfirmasi Penerimaan</h2>
                    <p class="os-card-subtitle">Unggah scan BAST/SBBK bertanda tangan, lalu isi identitas penerima.</p>
                </x-slot:title>

                <form method="POST" action="{{ route('distribusi.confirm', $distribution) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="os-section-tight">
                        <div class="os-field">
                            <label class="os-label" for="file_tanda_terima">Scan BAST / SBBK bertanda tangan</label>
                            <input id="file_tanda_terima" type="file" name="file_tanda_terima"
                                   accept=".pdf,.jpg,.jpeg,.png" class="os-input" required>
                            <div class="os-help">Wajib. PDF/JPG/PNG, maks. 5 MB. Disimpan sebagai arsip digital.</div>
                            @error('file_tanda_terima') <span class="os-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="os-field os-section-tight">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" x-model="useMyData" @change="toggleMyData($event.target.checked)"
                                       class="h-4 w-4 rounded border-control-border">
                                <span class="os-label mb-0">Saya yang menerima</span>
                            </label>
                            <div class="os-help">
                                Centang untuk memakai nama, NIP, jabatan, dan unit kerja akun Anda. Kosongkan bila penerima adalah orang lain.
                            </div>
                        </div>

                        <div class="os-form-grid">
                            <div class="os-floating">
                                <input type="text" name="penerima_nama" placeholder=" " x-model="form.penerima_nama"
                                       :disabled="useMyData" class="os-floating-input">
                                <label class="os-floating-label">Nama penerima</label>
                                @error('penerima_nama') <span class="os-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="os-floating">
                                <input type="text" name="penerima_nip" placeholder=" " x-model="form.penerima_nip"
                                       :disabled="useMyData" class="os-floating-input">
                                <label class="os-floating-label">NIP penerima</label>
                                @error('penerima_nip') <span class="os-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="os-floating">
                                <input type="text" name="penerima_jabatan" placeholder=" " x-model="form.penerima_jabatan"
                                       :disabled="useMyData" class="os-floating-input">
                                <label class="os-floating-label">Jabatan penerima</label>
                                @error('penerima_jabatan') <span class="os-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="os-floating">
                                <input type="text" name="penerima_unit" placeholder=" " x-model="form.penerima_unit"
                                       :disabled="useMyData" class="os-floating-input">
                                <label class="os-floating-label">Unit kerja penerima</label>
                                @error('penerima_unit') <span class="os-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <p class="os-error os-section-tight" x-show="formError" x-text="formError"></p>

                    <x-slot:footer>
                        <button type="button" class="os-btn os-btn--secondary" @click="confirmModal = false">
                            <x-icon name="close-line" /> Batal
                        </button>
                        <button type="submit" class="os-btn os-btn--primary">
                            <x-icon name="check-line" /> Konfirmasi Terima
                        </button>
                    </x-slot:footer>
                </form>
            </x-modal>
        @endif
    @endcan
    </div>
@endsection
