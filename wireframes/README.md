# Wireframe UI — Manajemen Stok Obat

Kumpulan wireframe statis untuk review antarmuka sebelum implementasi Laravel /
Livewire. File-file di sini **bukan** kode aplikasi: tanpa Composer, tanpa Livewire,
tanpa build step.

## Cara membuka

Buka `wireframes/index.html` di browser.
Itu hub yang menautkan seluruh layar.
Alternatif: jalankan server statis dari root proyek.

```bash
python -m http.server 8000
# lalu buka http://localhost:8000/wireframes/
```

## Pemilih role

Setiap halaman (kecuali login) memiliki pemilih **Role** di header kanan atas.
Pilihan tersimpan di `localStorage` browser dengan kunci `wireframe-role`.

| Role | Fokus akses |
| --- | --- |
| Puskesmas | LPLPO, permintaan, konfirmasi terima, laporan own |
| Gudang | Master obat, stok, penerimaan, opname |
| Distribusi | Surat jalan & pengiriman |
| Apoteker | Verifikasi, approve, RKO, anggaran, audit log |
| Admin | Semua termasuk pengaturan |

Tombol atau baris dengan atribut `data-roles="..."` otomatis disembunyikan bila role
aktif tidak termasuk di dalamnya.
Sidebar juga dirender ulang sesuai role.

## Daftar file

| File | Layar |
| --- | --- |
| `index.html` | Hub semua layar |
| `00-shell.html` | Layout dasar & pustaka komponen |
| `01-login.html` | Login |
| `02-dashboard.html` | Dashboard |
| `03-master-obat.html` | Master obat |
| `04-master-kategori.html` | Kategori obat |
| `05-master-supplier.html` | Supplier |
| `06-master-facilities.html` | Puskesmas / fasilitas |
| `07-master-sumber-dana.html` | Sumber dana |
| `08-master-licenses.html` | Izin & CDOB |
| `09-stok-batch.html` | Batch obat |
| `10-stok-ledger.html` | Stock ledger |
| `11-stok-adjustment.html` | Penyesuaian stok |
| `12-stok-opname.html` | Stok opname |
| `13-lplpo.html` | LPLPO |
| `14-permintaan.html` | Permintaan obat |
| `15-rko.html` | RKO |
| `16-prediksi.html` | Prediksi kebutuhan obat |
| `17-anggaran.html` | Anggaran |
| `18-purchase-order.html` | Purchase order |
| `19-goods-receipt.html` | Goods receipt |
| `20-distribusi.html` | Distribusi |
| `21-laporan.html` | Laporan |
| `22-notifikasi.html` | Notifikasi |
| `23-audit-log.html` | Audit log |
| `24-pengaturan.html` | Pengaturan |

## Konvensi wireframe

- Semua halaman memakai `assets/wireframe.css` dan `assets/wireframe.js`.
- Konten tiap halaman dibungkus `<main class="page">`; JS menyisipkan sidebar dan
  header.
- Warna abu-abu dan kotak placeholder sengaja dipakai agar tidak terkesan sebagai desain
  final.
- Badge status mengikuti state machine di `BLUEPRINT.md` bagian 8.
- Catatan implementasi ditandai dengan `<span class="annot">` atau blok `.note`.
- Data bersifat dummy dan konsisten antarhalaman (misalnya `PMSK-2026-0012`,
  `AMX-2601`).

## Hubungan dengan blueprint

Layar-layar ini merangkum modul pada bagian 10 (RBAC), 11 (laporan & dashboard), 13
(struktur folder), dan 17 (endpoint API) dari `BLUEPRINT.md`. Perubahan status atau alur
pada blueprint harus diikuti oleh pembaruan wireframe terkait.
