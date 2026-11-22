@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Profil Saya</h1>
            <p class="os-page-subtitle">Identitas Anda untuk dokumen BAST distribusi.</p>
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
        <div class="os-section-tight os-form-grid">
            <div class="os-field">
                <span class="os-label">Nama</span>
                <div>{{ $user->name }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Email</span>
                <div>{{ $user->email }}</div>
            </div>
            <div class="os-field">
                <span class="os-label">Fasilitas</span>
                <div>{{ $user->facility?->nama ?? '—' }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('profil.update') }}">
            @csrf
            @method('PUT')

            <div class="os-section-tight os-form-grid">
                <div class="os-floating">
                    <input id="nip" type="text" name="nip" placeholder=" "
                           value="{{ old('nip', $user->nip) }}" class="os-floating-input">
                    <label class="os-floating-label" for="nip">NIP</label>
                    @error('nip') <span class="os-error">{{ $message }}</span> @enderror
                </div>
                <div class="os-floating">
                    <input id="jabatan" type="text" name="jabatan" placeholder=" "
                           value="{{ old('jabatan', $user->jabatan) }}" class="os-floating-input">
                    <label class="os-floating-label" for="jabatan">Jabatan</label>
                    @error('jabatan') <span class="os-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="os-section os-toolbar">
                <button type="submit" class="os-btn os-btn--primary">
                    <x-icon name="save-line" /> Simpan Profil
                </button>
            </div>
        </form>
    </div>

    <div class="os-help os-section">
        NIP dan jabatan dipakai untuk mengisi identitas penerima pada BAST. Nilai yang sudah tercatat di dokumen lama tidak berubah.
    </div>
@endsection
