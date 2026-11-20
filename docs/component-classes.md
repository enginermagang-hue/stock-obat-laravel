# Komponen CSS ObatStock

Katalog class UI di `resources/css/app.css`. Semua class didefinisikan di dalam
`@layer components` dengan `@apply`, sehingga halaman Blade cukup memakai class `os-*`
tanpa mengulang utilitas Tailwind.

## Design token

| Token | Nilai |
| --- | --- |
| Font | Inter via Google Fonts, fallback font sistem lewat `--font-sans` |
| Ukuran teks dasar | 15px |
| Skin | **Bordered** — permukaan putih, pemisah garis 1px, tanpa shadow |
| Primary | `#666cff` (`--color-primary`), varian gelap `#5c61e6`, lembut `#e7e8ff` |
| Halaman | `#ffffff` (`--color-page`), permukaan sekunder `#f7f7f9` |
| Garis | `#e5e6e8` (`--color-line`) |
| Teks | `#33374d` (`--color-heading`), `#3b4055` (`--color-ink`), sekunder `#676a7b`, lemah `#a8aab4` |
| Status | success `#72e128`, danger `#ff4d49`, warning `#fdb528`, info `#26c6f9` |
| Radius | Card `10px` (`--radius-card`), kontrol `8px` (`--radius-control`), input `8px` (`--radius-input`) |
| Border input | `#d1d0d5` (`--color-control-border`), disabled `#c5c7cf` |
| Disabled bg | `#f0f1f3` (`--color-input-disabled-bg`) |
| Tinggi input | `48px` (default), `54px` (`--lg`), `38px` (`--sm`) |

Token didefinisikan di `@theme` sehingga utilitas seperti `bg-primary` dan
`rounded-card` tersedia untuk `@apply`.

## Konvensi

- Prefix `os-` menghindari bentrok dengan utilitas Tailwind dan tema pihak ketiga.
- Modifier memakai pola `--variant`, misalnya `os-btn--primary` dan
  `os-status-pill--danger`.
- Class dikelompokkan per fungsi: shell, card, KPI, filter, tabel, form, feedback, dan
  modal.
- Visual mengikuti skin **Bordered** dari Materialize admin: permukaan putih, kartu dan
  menu dipisah garis 1px `#e5e6e8`, tanpa shadow pada kartu, tombol, maupun dropdown.
- Inter 15px, primary `#666cff`, radius kartu 10px, kontrol 8px, input 8px/48px, dan
  label 13px/400 di atas input sebagai default.
  Floating label hanya varian.
- Fokus input hanya mengubah warna border, tanpa ring atau shadow.
- Ikon, logo, dan ilustrasi dibuat sendiri; aset template pihak ketiga tidak disalin.

## Shell

| Class | Fungsi |
| --- | --- |
| `os-shell` | Kontainer utama satu halaman. |
| `os-sidebar` | Panel navigasi kiri. |
| `os-sidebar-brand` | Area logo di atas sidebar. |
| `os-sidebar-brand-name`, `os-sidebar-brand-caption` | Nama aplikasi dan deskripsi kecil. |
| `os-nav-parent` | Menu tingkat pertama beserta caret. |
| `os-nav-parent--open` | Menu induk saat grup terbuka (latar abu). |
| `os-nav-caret` | Panah pada menu induk. |
| `os-nav-group` | Judul grup menu (opsional). |
| `os-nav-link` | Item menu tingkat kedua, tinggi 38px. |
| `os-nav-link--sub` | Indentasi item menu 40.8px. |
| `os-nav-link--active` | Item menu aktif dengan latar primary dan teks putih. |
| `os-topbar` | Header kanan atas. |
| `os-topbar-crumb` | Breadcrumb; elemen `strong` di dalamnya ditebalkan. |
| `os-topbar-spacer` | Mengisi ruang kosong di topbar. |
| `os-role-badge` | Badge role pengguna. |
| `os-content`, `os-main` | Area konten dan paddingnya. |
| `os-page-title`, `os-page-subtitle` | Judul dan subjudul halaman. |
| `os-section`, `os-section-tight` | Spasi vertikal antar bagian (20px / 16px). |
| `os-grid-2` | Grid dua kolom untuk kartu. |
| `os-guest-page` | Halaman tanpa shell, misalnya login. |

```blade
<div class="os-shell">
    <aside class="os-sidebar">
        <div class="os-sidebar-brand">
            <div class="os-sidebar-brand-name">ObatStock</div>
            <div class="os-sidebar-brand-caption">Manajemen stok obat</div>
        </div>
        <div class="os-nav-parent">Utama <span class="os-nav-caret">▾</span></div>
        <a class="os-nav-link os-nav-link--sub os-nav-link--active" href="{{ route('dashboard') }}">Dashboard</a>
    </aside>
    <div class="os-content">
        <header class="os-topbar">...</header>
        <main class="os-main">...</main>
    </div>
</div>
```

## Card

| Class | Fungsi |
| --- | --- |
| `os-card` | Kartu dasar: radius 10px, garis 1px `#e5e6e8` (tanpa shadow), sesuai skin Bordered. |
| `os-card--flush` | Kartu tanpa padding, untuk tabel. |
| `os-card-head`, `os-card-body`, `os-card-footer` | Bagian kartu. |
| `os-card-title`, `os-card-subtitle` | Judul dan keterangan kartu. |

## KPI

| Class | Fungsi |
| --- | --- |
| `os-kpi-grid` | Grid kartu ringkasan. |
| `os-kpi-card` | Kartu KPI putih bergaris 1px. |
| `os-kpi-head` | Baris ikon dan tren. |
| `os-kpi-icon`, `os-kpi-icon--success`, `--warning`, `--info` | Kotak ikon 40px dengan latar lembut. |
| `os-kpi-value`, `os-kpi-label` | Angka 18px/500 dan label 15px. |
| `os-kpi-trend`, `os-kpi-trend--down` | Tren 15px hijau (`--down` merah). |
| `os-kpi-chip` | Pill abu 13px di bawah label. |

```blade
<div class="os-kpi-card">
    <div class="os-kpi-head">
        <div class="os-kpi-icon">✚</div>
        <span class="os-kpi-trend">+5 ↑</span>
    </div>
    <div class="os-kpi-value">37</div>
    <div class="os-kpi-label">Jenis obat</div>
    <span class="os-kpi-chip">Data aktif</span>
</div>
```

## Filter dan tombol

| Class | Fungsi |
| --- | --- |
| `os-filter-bar` | Baris filter atas kartu tabel. |
| `os-filter` | Pembungkus select filter. |
| `os-filter-select`, `os-search`, `os-per-page` | Input filter, pencarian, dan jumlah baris. |
| `os-btn` | Tombol dasar: padding `7.7×22px`, radius 8px, font 15px/500, letter-spacing `.4px`. |
| `os-btn--primary`, `--secondary`, `--danger`, `--ghost` | Varian tombol; sesuai skin Bordered semua tombol tanpa shadow. |
| `os-btn--sm` | Tombol kecil: font 13px, padding `7×15.5px`, radius 6px. |
| `os-btn-icon` | Tombol berupa ikon teks. |
| `os-toolbar` | Baris kumpulan tombol. |

```blade
<div class="os-filter-bar">
    <select class="os-filter-select">...</select>
    <input class="os-search" placeholder="Cari data...">
    <button class="os-btn os-btn--primary">+ Tambah</button>
</div>
```

## Progress stack

| Class | Fungsi |
| --- | --- |
| `os-progress-stack` | Batang progres bertumpuk. |
| `os-progress-segment` | Segmen progres. |
| `os-progress-legend`, `os-progress-legend-item`, `os-progress-legend-dot` | Legenda di bawah progres. |

Untuk Fase 2, komponen ini dipakai sebagai “Komposisi stok per status batch”.

## Login

| Class | Fungsi |
| --- | --- |
| `os-login-card` | Kartu form login. |
| `os-login-logo`, `os-login-title` | Logo dan judul. |
| `os-login-form`, `os-login-row` | Form dan baris field. |
| `os-login-remember`, `os-login-submit`, `os-login-hint` | Ingat saya, tombol masuk, dan catatan demo. |

## Tabel

| Class | Fungsi |
| --- | --- |
| `os-table-wrap` | Pembungkus `overflow-x-auto`. |
| `os-table` | Tabel dasar. |
| `os-table--striped`, `os-table--hover` | Baris bergaris dan efek hover. |
| `os-tr--selected` | Baris terpilih. |
| `os-td-right` | Sel dengan konten rata kanan. |
| `os-table-actions` | Kumpulan tombol aksi di tabel. |

## Status pill

| Class | Fungsi |
| --- | --- |
| `os-status-pill` | Badge status dasar. |
| `os-status-pill--draft` | Abu: draft, nonaktif. |
| `os-status-pill--process`, `--verified` | Biru: dikirim, proses, diverifikasi. |
| `os-status-pill--approved`, `--success` | Hijau: disetujui, selesai, valid. |
| `os-status-pill--warning` | Kuning: sebagian, menipis, hampir expired. |
| `os-status-pill--danger` | Merah: ditolak, expired, dibatalkan. |
| `os-status-pill--info` | Biru muda: informasi netral. |

## Form

| Class | Fungsi |
| --- | --- |
| `os-form-grid` | Grid dua kolom. |
| `os-field`, `os-field--inline` | Field dan variasi sejajar. |
| `os-label`, `os-help`, `os-error` | Label, bantuan, dan pesan error. |
| `os-input`, `os-select`, `os-textarea` | Kontrol dasar 48px / radius 8px; fokus hanya mengubah border, tanpa ring/shadow. |
| `os-input--lg`, `os-select--lg`, `os-input--sm`, `os-select--sm` | Varian ukuran 54px / 38px. |
| `os-input--rounded`, `os-input--plaintext`, `os-select--plaintext` | Varian bentuk dan tampilan tanpa border. |
| `os-select--multiple` | Select multiple dengan tinggi minimum 80px. |
| `:disabled`, `os-input--disabled` | State nonaktif. |
| `os-checkbox`, `os-radio`, `os-switch`, `os-toggle` | Kontrol pilihan. |
| `os-floating`, `os-floating-input`, `os-floating-label` | Varian floating label (bukan default). |

Default memakai label 13px di atas input:

```blade
<div class="os-field">
    <label for="email" class="os-label">Email</label>
    <input id="email" type="email" class="os-input" wire:model="email">
    <span class="os-help">Kami tidak akan membagikan detail Anda.</span>
</div>
```

Floating label dipakai hanya jika diinginkan:

```blade
<div class="os-floating">
    <input type="email" placeholder=" " class="os-floating-input" wire:model="email">
    <label class="os-floating-label" for="email">Email</label>
</div>
```

## Feedback

| Class | Fungsi |
| --- | --- |
| `os-alert` dan `os-alert--info`, `--warning`, `--danger`, `--success` | Notifikasi sebaris. |
| `os-tabs`, `os-tab`, `os-tab--active` | Navigasi tab. |
| `os-empty` | Kondisi tanpa data. |
| `os-placeholder` | Area grafik atau konten yang belum tersedia. |

## Modal

| Class | Fungsi |
| --- | --- |
| `os-modal-backdrop` | Latar belakang modal. |
| `os-modal-panel`, `os-modal-head`, `os-modal-body`, `os-modal-footer` | Bagian modal. |

Dipakai untuk konfirmasi hapus, pembatalan opname, dan peringatan mode buta.

## Lainnya

| Class | Fungsi |
| --- | --- |
| `os-pagination`, `os-pagination-info` | Area pagination dan keterangan entri. |
| `os-timeline`, `os-timeline-item`, `os-timeline-dot`, `os-timeline-time` | Linimasa aktivitas. |
| `os-avatar` | Inisial pengguna. |
| `os-avatar-online` | Avatar dengan titik hijau di pojok. |
| `os-badge-count` | Badge angka 24×24 lingkaran merah `#ff4d49`. |
| `os-search-field`, `os-search-shortcut` | Kolom pencarian global dan penanda shortcuts. |
| `os-icon-btn` | Tombol ikon bundar 38px di topbar. |
| `os-menu-dot`, `os-menu-badge` | Bullet menu dan badge jumlah. |
| `os-dropdown`, `os-dropdown-item` | Menu dropdown bergaris tanpa shadow. |

## Layout dashboard

| Class | Fungsi |
| --- | --- |
| `os-container` | Konten centered dengan maksimum 1392px. |
| `os-dash-grid` | Grid dashboard 12 kolom dengan gap 24px. |
| `os-col-2` s.d. `os-col-8` | Span kolom pada layar menengah ke atas. |
| `os-banner`, `os-banner-art` | Banner sambutan dan dekorasi CSS. |
| `os-chart` | Placeholder grafik bergaris putus-putus. |
| `os-progress` | Bar progres dengan span warna primary. |
| `os-metric-value`, `os-metric-label` | Angka metrik 24px dan label 13px. |
| `os-footer` | Footer aplikasi. |

## Ikon

Ikon memakai **Remix Icon v4.9.1** (Remix Icon License v1.0, bebas untuk penggunaan
personal dan komersial).
CSS diimpor dari `remixicon/fonts/remixicon.css` sehingga file font dibundel Vite secara
lokal.

| Class | Fungsi |
| --- | --- |
| `os-icon` | Dasar ikon inline. |
| `os-icon-16`, `os-icon-18`, `os-icon-20`, `os-icon-24` | Ukuran ikon dalam px. |
| `os-search-wrap` | Pembungkus input dengan ikon di dalam. |

Komponen Blade `x-icon` menyederhanakan pemakaian:

```blade
<x-icon name="capsule-line" :size="20" />
```

Ikon yang dipakai: `home-smile-line`, `capsule-line`, `store-2-line`, `wallet-3-line`,
`hospital-line`, `menu-fold-line`, `arrow-down-s-line`, `search-line`,
`notification-3-line`, `sun-line`, `heart-pulse-line`, `arrow-up-line`,
`arrow-right-line`, `add-line`, `edit-line`, `delete-bin-line`, `save-line`, dan
`close-line`.
