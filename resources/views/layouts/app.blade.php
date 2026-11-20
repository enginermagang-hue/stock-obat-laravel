<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
@php
    $user = auth()->user();
    $groupIcons = [
        'Utama' => 'home-smile-line',
        'Master Data' => 'apps-2-line',
        'Stok' => 'archive-2-line',
        'Pembelian' => 'shopping-cart-2-line',
    ];
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
        'Stok' => [
            ['label' => 'Batch Obat', 'route' => 'stok.batch', 'permission' => 'manage-stock'],
            ['label' => 'Stock Ledger', 'route' => 'stok.ledger', 'permission' => 'manage-stock'],
            ['label' => 'Penyesuaian', 'route' => 'stok.adjustment', 'permission' => 'manage-stock'],
            ['label' => 'Stok Opname', 'route' => 'stok.opname', 'permission' => 'input-opname'],
        ],
        'Pembelian' => [
            ['label' => 'Purchase Order', 'route' => 'pembelian.purchase-order', 'permission' => 'receive-goods'],
            ['label' => 'Penerimaan', 'route' => 'pembelian.penerimaan', 'permission' => 'receive-goods'],
        ],
    ];
@endphp
<div class="os-shell"
     x-data="{ collapsed: localStorage.getItem('os-sidebar-collapsed') === 'true' }"
     :class="{ 'os-shell--collapsed': collapsed }">
    <aside class="os-sidebar">
        <div class="os-sidebar-brand">
            <div class="os-sidebar-logo">OS</div>
            <div class="os-sidebar-brand-text">
                <div class="os-sidebar-brand-name">ObatStock</div>
                <div class="os-sidebar-brand-caption">Manajemen stok obat</div>
            </div>
            <button type="button" class="os-icon-btn !h-8 !w-8"
                    @click="collapsed = !collapsed; localStorage.setItem('os-sidebar-collapsed', collapsed)"
                    :aria-label="collapsed ? 'Perbesar menu' : 'Perkecil menu'"
                    x-bind:title="collapsed ? 'Perbesar menu' : 'Perkecil menu'">
                <x-icon x-show="!collapsed" name="menu-fold-line" :size="18" />
                <x-icon x-show="collapsed" name="menu-unfold-line" :size="18" />
            </button>
        </div>

        <div class="os-sidebar-nav">
            @foreach ($menu as $group => $items)
                @php
                    $visible = array_filter($items, fn ($item) => $item['permission'] === null || $user->can($item['permission']));
                    $groupActive = collect($visible)->contains(fn ($item) => request()->routeIs($item['route']));
                @endphp
                @if (count($visible) > 0)
                    <div class="os-nav-parent {{ $groupActive ? 'os-nav-parent--open' : '' }}">
                        <span class="flex items-center gap-2">
                            <x-icon :name="$groupIcons[$group] ?? 'apps-2-line'" :size="22" />
                            <span class="os-nav-label">{{ $group }}</span>
                        </span>
                        <span class="os-nav-parent-meta">
                            <span class="os-menu-badge">{{ count($visible) }}</span>
                            <x-icon name="arrow-down-s-line" :size="14" class="os-nav-caret" />
                        </span>
                    </div>
                    @foreach ($visible as $item)
                        <a href="{{ route($item['route']) }}"
                           class="os-nav-link os-nav-link--sub {{ request()->routeIs($item['route']) ? 'os-nav-link--active' : '' }}">
                            <span class="os-nav-label">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                @endif
            @endforeach
        </div>
    </aside>

    <div class="os-content">
        <header class="os-topbar">
            <livewire:global-search />
            <div class="os-topbar-spacer"></div>
            <button type="button" class="os-icon-btn" aria-label="Notifikasi">
                <span class="relative">
                    <x-icon name="notification-3-line" :size="18" />
                    <span class="os-menu-badge absolute -right-2 -top-2">3</span>
                </span>
            </button>
            <button type="button" class="os-icon-btn" aria-label="Tema">
                <x-icon name="sun-line" :size="18" />
            </button>
            <div class="relative" x-data="{ open: false }">
                <button type="button" class="os-avatar-online" @click="open = !open" aria-label="Menu pengguna">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </button>
                <div x-show="open" @click.outside="open = false" class="os-dropdown absolute right-0 top-12 z-40 w-44">
                    <div class="border-b border-line px-4 py-2">
                        <div class="text-[13px] font-medium text-heading">{{ $user->name }}</div>
                        <div class="text-[11px] text-faint">{{ $user->email }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="os-dropdown-item w-full">Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="os-main">
            <div class="os-container">
                {{ $slot }}
            </div>
        </main>
        <footer class="os-footer">
            <div class="os-container">© {{ date('Y') }} Manajemen Stok Obat</div>
        </footer>
    </div>
</div>
@livewireScripts
</body>
</html>
