<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? trim($__env->yieldContent('title')) ?: 'Dashboard' }} — {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $user = auth()->user();
    $groupIcons = [
        'Utama' => 'home-smile-line',
        'Master Data' => 'apps-2-line',
        'Stok' => 'archive-2-line',
        'Pembelian' => 'shopping-cart-2-line',
        'Perencanaan' => 'file-list-3-line',
        'Keuangan' => 'wallet-3-line',
    ];
    $sectionHeaders = [
        'Utama' => 'Menu Utama',
        'Master Data' => 'Master & Operasional',
        'Perencanaan' => 'Perencanaan',
        'Keuangan' => 'Keuangan',
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
            ['label' => 'Izin & CDOB', 'route' => 'master.licenses', 'permission' => 'view-master'],
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
        'Perencanaan' => [
            ['label' => 'LPLPO', 'route' => 'lplpo.index', 'permission' => 'view-lplpo'],
            ['label' => 'Permintaan', 'route' => 'permintaan.index', 'permission' => 'view-permintaan'],
            ['label' => 'Distribusi', 'route' => 'distribusi.index', 'permission' => 'view-distribution'],
            ['label' => 'RKO', 'route' => 'rko.index', 'permission' => 'manage-rko'],
            ['label' => 'Prediksi', 'route' => 'prediksi.index', 'permission' => 'manage-prediksi'],
        ],
        'Keuangan' => [
            ['label' => 'Anggaran & Realisasi', 'route' => 'anggaran.index', 'permission' => 'manage-anggaran'],
            ['label' => 'Laporan', 'route' => 'laporan.index', 'permission' => 'view-reports'],
            ['label' => 'Audit Log', 'route' => 'audit-log.index', 'permission' => 'view-audit-log'],
        ],
    ];
@endphp
<div class="os-shell"
     x-data="{ collapsed: localStorage.getItem('os-sidebar-collapsed') === 'true' }"
     :class="{ 'os-shell--collapsed': collapsed }">
    <aside class="os-sidebar">
        <div class="os-sidebar-brand">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 flex-1 items-center gap-2">
                <div class="os-sidebar-logo">OS</div>
                <div class="os-sidebar-brand-text">
                    <div class="os-sidebar-brand-name">ObatStock</div>
                </div>
            </a>
            <button type="button" class="os-icon-btn !h-6 !w-6 shrink-0"
                    @click="collapsed = !collapsed; localStorage.setItem('os-sidebar-collapsed', collapsed)"
                    :aria-label="collapsed ? 'Perbesar menu' : 'Perkecil menu'"
                    x-bind:title="collapsed ? 'Perbesar menu' : 'Perkecil menu'">
                <x-icon x-show="!collapsed" name="arrow-left-double-line" class="os-icon--lg" />
                <x-icon x-show="collapsed" name="arrow-right-double-line" class="os-icon--lg" />
            </button>
        </div>

        <div class="os-sidebar-shadow"></div>

        <div class="os-sidebar-nav">
            @foreach ($menu as $group => $items)
                @php
                    $visible = array_filter($items, fn ($item) => $item['permission'] === null || $user->can($item['permission']));
                    $groupActive = collect($visible)->contains(fn ($item) => request()->routeIs($item['route']));
                @endphp
                @if (count($visible) > 0)
                    <div x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }"
                         data-group="{{ $group }}"
                         @os-open-group.window="if ($event.detail === '{{ $group }}') open = true">
                        @if (isset($sectionHeaders[$group]))
                            <div class="os-nav-header"><span>{{ $sectionHeaders[$group] }}</span></div>
                        @endif
                        <button type="button"
                                class="os-nav-parent"
                                :class="{ 'os-nav-parent--open': open }"
                                :aria-expanded="open"
                                @click="open = !open">
                            <span class="flex items-center gap-2">
                                <x-icon :name="$groupIcons[$group] ?? 'apps-2-line'" />
                                <span class="os-nav-label">{{ $group }}</span>
                            </span>
                            <span class="os-nav-parent-meta">
                                @php($badge = $sidebarBadges[$group] ?? 0)
                                @if ($badge > 0)
                                    <span class="os-menu-badge">{{ $badge > 99 ? '99+' : $badge }}</span>
                                @endif
                                <x-icon name="arrow-down-s-line" class="os-nav-caret" />
                            </span>
                        </button>
                        <div x-show="open" x-cloak>
                            @foreach ($visible as $item)
                                <a href="{{ route($item['route']) }}"
                                   class="os-nav-link os-nav-link--sub {{ request()->routeIs($item['route']) ? 'os-nav-link--active' : '' }}">
                                    <span class="os-nav-label">{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </aside>

    <div class="os-content">
        <header class="os-topbar">
            <x-global-search />
            <div class="os-topbar-spacer"></div>
            <div class="os-topbar-actions">
                <div class="relative" x-data="{ open: false }">
                    <button type="button" class="os-icon-btn" @click="open = !open" aria-label="Bahasa">
                        <x-icon name="translate-2" />
                    </button>
                    <div x-show="open" @click.outside="open = false" class="os-dropdown absolute right-0 top-11 z-40 w-40">
                        <button type="button" class="os-dropdown-item">English</button>
                        <button type="button" class="os-dropdown-item">Indonesia</button>
                    </div>
                </div>
                <div class="relative" x-data="{ open: false }">
                    <button type="button" class="os-icon-btn" @click="open = !open" aria-label="Tema">
                        <x-icon name="sun-line" />
                    </button>
                    <div x-show="open" @click.outside="open = false" class="os-dropdown absolute right-0 top-11 z-40 w-40">
                        <button type="button" class="os-dropdown-item">Terang</button>
                        <button type="button" class="os-dropdown-item">Gelap</button>
                        <button type="button" class="os-dropdown-item">Sistem</button>
                    </div>
                </div>
                <div class="relative" x-data="{ open: false }">
                    <button type="button" class="os-icon-btn" @click="open = !open" aria-label="Shortcut">
                        <x-icon name="star-smile-line" />
                    </button>
                    <div x-show="open" @click.outside="open = false" class="os-dropdown absolute right-0 top-11 z-40 w-48">
                        <a href="{{ route('dashboard') }}" class="os-dropdown-item">Dashboard</a>
                        @if ($user->can('manage-stock'))
                            <a href="{{ route('stok.batch') }}" class="os-dropdown-item">Batch Obat</a>
                        @endif
                        @if ($user->can('view-reports'))
                            <a href="{{ route('laporan.index') }}" class="os-dropdown-item">Laporan</a>
                        @endif
                    </div>
                </div>
                <button type="button" class="os-icon-btn relative" aria-label="Notifikasi">
                    <x-icon name="notification-2-line" />
                    <span class="os-notif-dot"></span>
                </button>
                <div class="relative ml-1" x-data="{ open: false }">
                    <button type="button" class="os-avatar-online" @click="open = !open" aria-label="Menu pengguna">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </button>
                    <div x-show="open" @click.outside="open = false" class="os-dropdown absolute right-0 top-12 z-40 w-44">
                        <div class="border-b border-line px-4 py-2">
                            <div class="text-[13px] font-medium text-heading">{{ $user->name }}</div>
                            <div class="text-[11px] text-faint">{{ $user->email }}</div>
                        </div>
                        @if ($user->facility_id)
                            <a href="{{ route('facility.logo') }}" class="os-dropdown-item">Logo Puskesmas</a>
                        @endif
                        <a href="{{ route('profil') }}" class="os-dropdown-item">Profil Saya</a>
                        @if ($user->can('manage-users'))
                            <a href="{{ route('pengaturan.kop') }}" class="os-dropdown-item">Pengaturan Kop</a>
                            <a href="{{ route('pengaturan.pengguna') }}" class="os-dropdown-item">Pengguna</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="os-dropdown-item w-full">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="os-main">
            <div class="os-container">
                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </main>
        <footer class="os-footer">
            <div class="os-container">© {{ date('Y') }} Manajemen Stok Obat</div>
        </footer>
    </div>
</div>
</body>
</html>
