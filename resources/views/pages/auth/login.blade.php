@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="os-login-card">
        <div class="os-login-logo">OS</div>
        <h1 class="os-login-title">{{ config('app.name') }}</h1>
        <p class="os-page-subtitle">Gudang farmasi pusat &amp; distribusi puskesmas.</p>

        <form method="POST" action="{{ route('login.store') }}" class="os-login-form">
            @csrf
            <div class="os-login-row os-floating">
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username"
                       placeholder=" " class="os-floating-input" required autofocus>
                <label class="os-floating-label" for="email">Email</label>
                @error('email') <span class="os-error">{{ $message }}</span> @enderror
            </div>
            <div class="os-login-row os-floating">
                <input id="password" name="password" type="password" autocomplete="current-password"
                       placeholder=" " class="os-floating-input" required>
                <label class="os-floating-label" for="password">Password</label>
                @error('password') <span class="os-error">{{ $message }}</span> @enderror
            </div>
            <label class="os-login-remember">
                <input type="checkbox" name="remember" value="1" class="os-checkbox"> Ingat saya
            </label>
            <button type="submit" class="os-login-submit">
                Masuk
            </button>
        </form>
        <p class="os-login-hint">Demo: admin@rs.go.id / apoteker@rs.go.id / gudang@rs.go.id / distribusi@rs.go.id / admin@pkm007.go.id — password: <code>password</code></p>
    </div>
@endsection
