<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800">
@php
    $user = auth()->user();
    $menu = [
        'Utama' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'permission' => null],
        ],
        'Master Data' => [
            ['label' => 'Obat', 'route' => 'master.obat', 'permission' => 'view-master'],
            ['label' => 'Supplier', 'route' => 'master.supplier', 'permission' => 'view-master'],
            ['label' => 'Sumber Dana', 'route' => 'master.sumber-dana', 'permission' => 'view-master'],
            ['label' => 'Puskesmas', 'route' => 'master.facilities', 'permission' => 'view-master'],
        ],
    ];
@endphp
<div class="flex min-h-screen">
    <aside class="w-60 shrink-0 border-r border-slate-200 bg-slate-100 px-3 py-4">
        <div class="px-3 pb-4 border-b border-slate-200">
            <div class="font-bold">ObatStock</div>
            <div class="text-xs text-slate-500">Manajemen stok obat</div>
        </div>
        @foreach ($menu as $group => $items)
            @php
                $visible = array_filter($items, fn ($item) => $item['permission'] === null || $user->can($item['permission']));
            @endphp
            @if (count($visible) > 0)
                <div class="mt-5 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $group }}</div>
                @foreach ($visible as $item)
                    <a href="{{ route($item['route']) }}"
                       class="mt-1 block rounded px-3 py-2 text-sm {{ request()->routeIs($item['route']) ? 'bg-slate-800 text-white' : 'hover:bg-slate-200' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @endif
        @endforeach
    </aside>

    <div class="min-w-0 flex-1">
        <header class="flex h-16 items-center gap-4 border-b border-slate-200 bg-white px-6">
            <div class="text-sm text-slate-500">Wireframe → <strong class="text-slate-800">{{ $title ?? 'Dashboard' }}</strong></div>
            <div class="flex-1"></div>
            <span class="rounded-full bg-sky-100 px-3 py-1 text-xs text-sky-800">
                {{ $user->getRoleNames()->first() ?? 'tanpa role' }}
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-slate-500 hover:text-slate-900">Keluar</button>
            </form>
        </header>
        <main class="p-6">
            {{ $slot }}
        </main>
    </div>
</div>
@livewireScripts
</body>
</html>
