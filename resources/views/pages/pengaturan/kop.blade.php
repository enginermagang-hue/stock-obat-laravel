@extends('layouts.app')

@section('title', 'Pengaturan Kop')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Pengaturan Kop</h1>
            <p class="os-page-subtitle">Teks dan logo kop untuk Surat Jalan, SBBK, dan BAST distribusi.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="os-btn os-btn--secondary">
            <x-icon name="arrow-left-line" /> Kembali
        </a>
    </div>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="os-alert os-alert--danger os-section-tight">{{ $errors->first() }}</div>
    @endif

    <div class="os-section os-card os-card-body">
        <h2 class="os-card-title">Logo Dinas Kesehatan</h2>
        <div class="os-section-tight flex items-center gap-4">
            @if ($hasLogo)
                <img src="{{ route('pengaturan.kop.logo') }}" alt="Logo Dinas Kesehatan"
                     class="h-20 w-20 rounded border border-line bg-white object-contain p-1">
            @else
                <div class="os-empty flex h-20 w-20 items-center justify-center rounded border border-dashed border-control-border text-[12px] text-faint">
                    Belum ada
                </div>
            @endif
            <div class="os-help">PNG/JPG, maks. 2 MB. Disarankan gambar persegi dengan latar transparan/putih.</div>
        </div>

        <form method="POST" action="{{ route('pengaturan.kop.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="os-section-tight os-form-grid">
                <div class="os-floating">
                    <input id="line1" type="text" name="line1" placeholder=" "
                           value="{{ old('line1', $lines['line1']) }}" class="os-floating-input">
                    <label class="os-floating-label" for="line1">Baris 1</label>
                    @error('line1') <span class="os-error">{{ $message }}</span> @enderror
                </div>
                <div class="os-floating">
                    <input id="line2" type="text" name="line2" placeholder=" "
                           value="{{ old('line2', $lines['line2']) }}" class="os-floating-input">
                    <label class="os-floating-label" for="line2">Baris 2</label>
                    @error('line2') <span class="os-error">{{ $message }}</span> @enderror
                </div>
                <div class="os-floating">
                    <input id="line3" type="text" name="line3" placeholder=" "
                           value="{{ old('line3', $lines['line3']) }}" class="os-floating-input">
                    <label class="os-floating-label" for="line3">Alamat (baris 3)</label>
                    @error('line3') <span class="os-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="os-field os-section-tight">
                <label class="os-label" for="logo">Ganti logo dinas (opsional)</label>
                <input id="logo" type="file" name="logo" accept=".png,.jpg,.jpeg" class="os-input">
                @error('logo') <span class="os-error">{{ $message }}</span> @enderror
            </div>

            <div class="os-section os-toolbar">
                <button type="submit" class="os-btn os-btn--primary">
                    <x-icon name="save-line" /> Simpan Kop
                </button>
            </div>
        </form>
    </div>

    <div class="os-help os-section">
        Logo puskesmas diambil dari logo yang diunggah masing-masing puskesmas dan ditampilkan di sisi kanan kop.
    </div>
@endsection
