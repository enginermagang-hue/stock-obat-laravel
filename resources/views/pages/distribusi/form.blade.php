@extends('layouts.app')

@section('title', $title)

@section('content')
    @php
        $editing = $distribution !== null;
    @endphp

    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">{{ $title }}</h1>
            <p class="os-page-subtitle">
                {{ $editing
                    ? 'Perbaiki tanggal atau catatan surat jalan draft.'
                    : 'Buat surat jalan dari permintaan yang sudah disetujui. Item dipilih otomatis dengan FEFO.' }}
            </p>
        </div>
        <a href="{{ $editing ? route('distribusi.show', $distribution) : route('distribusi.index') }}" class="os-btn os-btn--secondary">
            <x-icon name="arrow-left-line" /> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="os-alert os-alert--danger os-section-tight">{{ $errors->first() }}</div>
    @endif

    <div class="os-section os-card os-card-body"
         @unless ($editing)
             x-data="distributionForm({
                 endpoint: '{{ route('distribusi.preview') }}',
                 permintaanId: '{{ old('permintaan_id') }}',
             })"
             x-init="if (permintaanId) loadPreview()"
         @endunless>
        <form method="POST"
              action="{{ $editing ? route('distribusi.update', $distribution) : route('distribusi.store') }}">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="os-section-tight os-form-grid">
                @if ($editing)
                    <div class="os-field">
                        <span class="os-label">Permintaan</span>
                        <div>
                            {{ $distribution->permintaan?->no_permintaan ?? '—' }}
                            @if ($distribution->facility)
                                · {{ $distribution->facility->nama }}
                            @endif
                        </div>
                    </div>
                @else
                    <div class="os-floating">
                        <select id="permintaan_id" name="permintaan_id"
                                x-model="permintaanId"
                                @change="loadPreview($event.target.value)"
                                class="os-floating-input os-select">
                            <option value="">— pilih permintaan —</option>
                            @foreach ($permintaans as $permintaan)
                                <option value="{{ $permintaan->id }}" @selected(old('permintaan_id') == $permintaan->id)>
                                    {{ $permintaan->no_permintaan }} · {{ $permintaan->facility?->nama }} · {{ $permintaan->status->value }}
                                </option>
                            @endforeach
                        </select>
                        <label class="os-floating-label" for="permintaan_id">Permintaan disetujui</label>
                        @error('permintaan_id') <span class="os-error">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="os-floating">
                    <input type="date" name="tanggal" placeholder=" "
                           value="{{ old('tanggal', $distribution?->tanggal?->toDateString() ?? now()->toDateString()) }}"
                           class="os-floating-input">
                    <label class="os-floating-label">Tanggal surat</label>
                    @error('tanggal') <span class="os-error">{{ $message }}</span> @enderror
                </div>

                <div class="os-floating">
                    <input type="text" name="catatan" placeholder=" " value="{{ old('catatan', $distribution?->catatan) }}" class="os-floating-input">
                    <label class="os-floating-label">Catatan</label>
                    @error('catatan') <span class="os-error">{{ $message }}</span> @enderror
                </div>
            </div>

            @unless ($editing)
                <div class="os-section-tight" x-show="loading" x-cloak>
                    <span class="os-help">Memuat pratinjau item…</span>
                </div>

                <div class="os-alert os-alert--danger os-section-tight" x-show="error" x-cloak x-text="error"></div>

                <template x-if="preview">
                    <div class="os-section-tight">
                        <div class="os-card-head">
                            <div>
                                <div class="os-card-title">Pratinjau item (FEFO)</div>
                                <div class="os-card-subtitle"
                                     x-text="`${preview.items.length} item · total ${formatRupiah(preview.total)}`"></div>
                            </div>
                        </div>

                        <div class="os-alert os-alert--warning os-section-tight" x-show="preview.warnings.length > 0" x-cloak>
                            <template x-for="warning in preview.warnings" :key="warning">
                                <div x-text="warning"></div>
                            </template>
                        </div>

                        <div class="os-table-wrap" x-show="preview.items.length > 0">
                            <table class="os-table">
                                <thead>
                                <tr>
                                    <th>Obat</th>
                                    <th class="os-td-right">Disetujui</th>
                                    <th class="os-td-right">Terpenuhi</th>
                                    <th class="os-td-right">Sisa</th>
                                    <th>Alokasi batch</th>
                                    <th class="os-td-right">Subtotal</th>
                                </tr>
                                </thead>
                                <tbody>
                                <template x-for="item in preview.items" :key="item.obat + '-' + item.kode">
                                    <tr>
                                        <td>
                                            <div class="font-medium text-heading" x-text="item.obat"></div>
                                            <div class="text-[13px] text-faint" x-text="item.kode"></div>
                                        </td>
                                        <td class="os-td-right" x-text="item.disetujui"></td>
                                        <td class="os-td-right" x-text="item.terpenuhi"></td>
                                        <td class="os-td-right">
                                            <span x-text="item.sisa"></span>
                                            <span class="text-danger" x-show="item.kekurangan > 0"
                                                  x-text="` (kurang ${item.kekurangan})`"></span>
                                        </td>
                                        <td>
                                            <template x-for="batch in item.batches" :key="batch.no_batch">
                                                <div class="text-[13px]">
                                                    <span class="font-medium text-heading" x-text="batch.no_batch"></span>
                                                    · <span x-text="batch.tanggal_kedaluwarsa"></span>
                                                    · <span x-text="`${batch.jumlah} unit`"></span>
                                                </div>
                                            </template>
                                            <div class="text-[13px] text-faint" x-show="item.batches.length === 0">
                                                Tidak ada batch tersedia
                                            </div>
                                        </td>
                                        <td class="os-td-right" x-text="formatRupiah(item.subtotal)"></td>
                                    </tr>
                                </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            @endunless

            <div class="os-section os-toolbar">
                <button type="submit" class="os-btn os-btn--primary">
                    <x-icon name="save-line" /> {{ $editing ? 'Simpan Perubahan' : 'Buat Draft' }}
                </button>
            </div>

            @if ($editing)
                <div class="os-help os-section-tight">
                    Item dan alokasi batch mengikuti permintaan serta FEFO sehingga tidak dapat diubah dari halaman ini.
                </div>
            @else
                <div class="os-help os-section-tight">
                    Jumlah yang didistribusikan mengikuti jumlah disetujui pada permintaan. Batch dipilih otomatis dengan FEFO.
                </div>
            @endif
        </form>
    </div>
@endsection
