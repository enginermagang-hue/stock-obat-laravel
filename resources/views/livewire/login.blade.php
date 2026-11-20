<div class="os-login-card">
    <div class="os-login-logo">OS</div>
    <h1 class="os-login-title">{{ config('app.name') }}</h1>
    <p class="os-page-subtitle">Gudang farmasi pusat & distribusi puskesmas.</p>

    <form wire:submit="login" class="os-login-form">
        <div class="os-login-row os-floating">
            <input id="email" type="email" wire:model="email" autocomplete="username"
                   placeholder=" " class="os-floating-input">
            <label class="os-floating-label" for="email">Email</label>
            @error('email') <span class="os-error">{{ $message }}</span> @enderror
        </div>
        <div class="os-login-row os-floating">
            <input id="password" type="password" wire:model="password" autocomplete="current-password"
                   placeholder=" " class="os-floating-input">
            <label class="os-floating-label" for="password">Password</label>
        </div>
        <label class="os-login-remember">
            <input type="checkbox" wire:model="remember" class="os-checkbox"> Ingat saya
        </label>
        <button type="submit" wire:loading.attr="disabled" class="os-login-submit">
            Masuk
        </button>
    </form>
    <p class="os-login-hint">Demo: admin@rs.go.id / apoteker@rs.go.id / gudang@rs.go.id / distribusi@rs.go.id / admin@pkm007.go.id — password: <code>password</code></p>
</div>
