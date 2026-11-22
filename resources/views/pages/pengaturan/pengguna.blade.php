@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="os-page-title">Pengguna</h1>
            <p class="os-page-subtitle">Kelola NIP dan jabatan pengguna untuk identitas dokumen BAST.</p>
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

    <div class="os-section os-card os-card--flush">
        <div class="os-table-wrap">
            <table class="os-table os-table--hover">
                <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Peran</th>
                    <th>Fasilitas</th>
                    <th>NIP</th>
                    <th>Jabatan</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td><span class="os-badge">{{ $user->roles->pluck('name')->implode(', ') }}</span></td>
                        <td>{{ $user->facility?->nama ?? '—' }}</td>
                        <td colspan="2">
                            <form method="POST" action="{{ route('pengaturan.pengguna.update', $user) }}"
                                  class="flex flex-wrap items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="nip" value="{{ old('nip', $user->nip) }}"
                                       placeholder="NIP" class="os-input" style="width: 150px">
                                <input type="text" name="jabatan" value="{{ old('jabatan', $user->jabatan) }}"
                                       placeholder="Jabatan" class="os-input" style="width: 200px">
                                <button type="submit" class="os-btn os-btn--sm os-btn--secondary">Simpan</button>
                            </form>
                        </td>
                        <td></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="os-empty">Belum ada pengguna.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="os-pagination">{{ $users->links() }}</div>
    </div>
@endsection
