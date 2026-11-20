<div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
    <div class="mb-5 grid h-11 w-11 place-items-center rounded-lg bg-slate-800 font-bold text-white">OS</div>
    <h1 class="text-xl font-semibold">{{ config('app.name') }}</h1>
    <p class="mt-1 text-sm text-slate-500">Gudang farmasi pusat & distribusi puskesmas.</p>

    <form wire:submit="login" class="mt-6">
        <div class="mb-4">
            <label class="mb-1 block text-xs font-semibold" for="email">Email</label>
            <input id="email" type="email" wire:model="email" autocomplete="username"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="nama@rs.go.id">
            @error('email') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
        </div>
        <div class="mb-4">
            <label class="mb-1 block text-xs font-semibold" for="password">Password</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <label class="mb-5 flex items-center gap-2 text-xs text-slate-500">
            <input type="checkbox" wire:model="remember"> Ingat saya
        </label>
        <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Masuk
        </button>
    </form>
    <p class="mt-5 text-xs text-slate-400">Demo: admin@rs.go.id / apoteker@rs.go.id / gudang@rs.go.id / distribusi@rs.go.id / admin@pkm007.go.id — password: <code>password</code></p>
</div>
