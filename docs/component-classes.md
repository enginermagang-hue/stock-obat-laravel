# Komponen CSS ObatStock

Katalog class UI di `resources/css/app.css`. Semua class didefinisikan di dalam
`@layer components` dengan `@apply`, sehingga halaman Blade cukup memakai class `os-*`
tanpa mengulang utilitas Tailwind.

## Design token

| Token | Nilai |
| --- | --- |
| Font | Inter, fallback ke font sistem lewat `--font-sans` |
| Ukuran teks dasar | 15px |
| Primary | `#666cff` (`--color-primary`), varian gelap `#585ce5`, lembut `#e7e8ff` |
| Surface | `#f7f7f9` (`--color-surface`) |
| Teks | `#3b4055` (`--color-ink`), sekunder `#676a7b`, lemah `#a8aab4` |
| Radius | Card `10px` (`--radius-card`), kontrol `8px` (`--radius-control`) |

Token didefinisikan di `@theme` sehingga utilitas seperti `bg-primary` dan
`rounded-card` tersedia untuk `@apply`.

## Konvensi

- Prefix `os-` menghindari bentrok dengan utilitas Tailwind dan tema pihak ketiga.
- Modifier memakai pola `--variant`, misalnya `os-btn--primary` dan
  `os-status-pill--danger`.
- Class dikelompokkan per fungsi: shell, card, KPI, filter, tabel, form, feedback, dan
  modal.
- Visual mengikuti pola Materialize admin: Inter 15px, primary `#666cff`, kartu radius
  10px, kontrol radius 8px, dan padding lega.
  Aset serta kode template pihak ketiga tidak disalin.

## Shell

| Class | Fungsi |
| --- | --- |
| `os-shell` | Kontainer utama satu halaman. |
| `os-sidebar` | Panel navigasi kiri. |
| `os-sidebar-brand` | Area logo di atas sidebar. |
| `os-sidebar-brand-name`, `os-sidebar-brand-caption` | Nama aplikasi dan deskripsi kecil. |
| `os-nav-parent` | Menu tingkat pertama beserta caret. |
| `os-nav-caret` | Panah pada menu induk. |
| `os-nav-group` | Judul grup menu (opsional). |
| `os-nav-link` | Item menu tingkat kedua. |
| `os-nav-link--sub` | Indentasi item menu 40px. |
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
| `os-card` | Kartu dasar. |
| `os-card--flush` | Kartu tanpa padding, untuk tabel. |
| `os-card-head`, `os-card-body`, `os-card-footer` | Bagian kartu. |
| `os-card-title`, `os-card-subtitle` | Judul dan keterangan kartu. |

## KPI

| Class | Fungsi |
| --- | --- |
| `os-kpi-grid` | Grid kartu ringkasan. |
| `os-kpi-card` | Kartu KPI dengan garis bawah berwarna. |
| `os-kpi-card--primary`, `--warning`, `--danger`, `--info` | Warna garis bawah. |
| `os-kpi-value`, `os-kpi-label` | Angka dan label. |
| `os-kpi-trend`, `os-kpi-trend--up`, `os-kpi-trend--down` | Indikator tren. |

```blade
<div class="os-kpi-card os-kpi-card--warning">
    <div class="os-kpi-value">37</div>
    <div class="os-kpi-label">Obat di bawah stok minimum</div>
    <div class="os-kpi-trend os-kpi-trend--down">-4.2%</div>
</div>
```

## Filter dan tombol

| Class | Fungsi |
| --- | --- |
| `os-filter-bar` | Baris filter atas kartu tabel. |
| `os-filter` | Pembungkus select filter. |
| `os-filter-select`, `os-search`, `os-per-page` | Input filter, pencarian, dan jumlah baris. |
| `os-btn` | Tombol dasar. |
| `os-btn--primary`, `--secondary`, `--danger`, `--ghost` | Varian tombol. |
| `os-btn--sm` | Tombol ukuran kecil. |
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
| `os-input`, `os-select`, `os-textarea` | Kontrol input. |
| `os-checkbox`, `os-toggle` | Kontrol pilihan. |
| `os-floating` | Pembungkus input dengan floating label. |
| `os-floating-input` | Input dengan placeholder kosong agar label bisa naik. |
| `os-floating-label` | Label yang naik saat fokus atau field berisi. |

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
| `os-badge-count` | Badge angka kecil pada notifikasi. |
