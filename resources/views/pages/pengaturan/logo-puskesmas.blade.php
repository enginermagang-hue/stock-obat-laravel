@extends('layouts.app')

@section('title', 'Logo Puskesmas')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Logo Puskesmas</h1>
            <p class="os-page-subtitle">
                Logo {{ $facility->nama }} untuk kop Surat Jalan, SBBK, dan BAST.
            </p>
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
        <h2 class="os-card-title">Logo saat ini</h2>
        <div class="os-section-tight flex items-center gap-4">
            @if ($hasLogo)
                <img src="{{ route('facility.logo.preview') }}" alt="Logo {{ $facility->nama }}"
                     class="h-24 w-24 rounded border border-line bg-white object-contain p-1">
            @else
                <div class="os-empty flex h-24 w-24 items-center justify-center rounded border border-dashed border-control-border text-[12px] text-faint">
                    Belum ada
                </div>
            @endif
            <div class="os-help">
                PNG/JPG, maks. 2 MB. Disarankan gambar persegi. Logo akan tampil di sisi kanan kop dokumen distribusi.
            </div>
        </div>

        <form method="POST" action="{{ route('facility.logo.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="os-field os-section-tight">
                <label class="os-label" for="logo">Unggah logo puskesmas</label>
                <input id="logo" type="file" name="logo" accept=".png,.jpg,.jpeg" class="os-input" required>
                @error('logo') <span class="os-error">{{ $message }}</span> @enderror
            </div>

            <div class="os-section os-toolbar">
                <button type="submit" class="os-btn os-btn--primary">
                    <x-icon name="upload-line" /> Simpan Logo
                </button>
            </div>
        </form>
    </div>

    <div class="os-help os-section">
        Hanya pengguna yang terhubung dengan fasilitas ini yang dapat mengunggah logo.
    </div>
@endsection
