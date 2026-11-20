<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
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
<div class="os-shell">
    <aside class="os-sidebar">
        <div class="os-sidebar-brand">
            <div class="os-sidebar-brand-name">ObatStock</div>
            <div class="os-sidebar-brand-caption">Manajemen stok obat</div>
        </div>
        @foreach ($menu as $group => $items)
            @php
                $visible = array_filter($items, fn ($item) => $item['permission'] === null || $user->can($item['permission']));
            @endphp
            @if (count($visible) > 0)
                <div class="os-nav-parent">
                    {{ $group }}
                    <span class="os-nav-caret">▾</span>
                </div>
                @foreach ($visible as $item)
                    <a href="{{ route($item['route']) }}"
                       class="os-nav-link os-nav-link--sub {{ request()->routeIs($item['route']) ? 'os-nav-link--active' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @endif
        @endforeach
    </aside>

    <div class="os-content">
        <header class="os-topbar">
            <div class="os-topbar-crumb">Wireframe → <strong>{{ $title ?? 'Dashboard' }}</strong></div>
            <div class="os-topbar-spacer"></div>
            <span class="os-role-badge">{{ $user->getRoleNames()->first() ?? 'tanpa role' }}</span>
            <div class="os-avatar">OS</div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="os-btn--ghost">Keluar</button>
            </form>
        </header>
        <main class="os-main">
            {{ $slot }}
        </main>
    </div>
</div>
@livewireScripts
</body>
</html>
