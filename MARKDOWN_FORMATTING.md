# Markdown Formatting

Proyek ini memakai dua formatter Markdown dengan peran berbeda.

## Flowmark (utama, otomatis)

Flowmark adalah formatter utama.
Ia berjalan otomatis lewat pre-commit hook dan menjadi instruksi default untuk agent
lewat `AGENTS.md`.

```bash
uvx --from flowmark-rs==0.4.0 flowmark --auto .
uvx --from flowmark-rs==0.4.0 flowmark --check .
```

## markdown-table-formatter (opsional, manual)

`markdown-table-formatter` v2.1.0 terpasang sebagai devDependency **hanya** untuk
merapikan tabel secara manual saat dibutuhkan.

```bash
npx markdown-table-formatter FILE.md
npx markdown-table-formatter --check FILE.md
```

## Konflik yang perlu diingat

Jangan menjalankan kedua formatter secara berurutan pada file yang sama:

- Flowmark me-render ulang tabel ke format kompak `| --- |` dan menghapus alignment
  kolom.
- markdown-table-formatter mengubah separator menjadi `|----|` dan menambahkan padding
  kolom.
- Output dari salah satu selalu membuat `--check` formatter lainnya gagal.

## Pengecualian: `BLUEPRINT.md`

`BLUEPRINT.md` sengaja diformat dengan markdown-table-formatter agar tabelnya sejajar.
Karena itu file ini tercantum di `.flowmarkignore`:

- Pre-commit hook memakai `--force-exclude`, sehingga Flowmark **melewati** file ini dan
  alignment tetap utuh.
- `flowmark --check .` juga melewati file ini.
- `npx markdown-table-formatter --check BLUEPRINT.md` tetap hijau.

Jangan menjalankan `flowmark --auto BLUEPRINT.md` secara eksplisit: file yang disebut
langsung menimpa aturan ignore, sehingga alignment akan hilang.
Jika itu terjadi, kembalikan dari `BLUEPRINT.md.orig` atau jalankan ulang
markdown-table-formatter.
