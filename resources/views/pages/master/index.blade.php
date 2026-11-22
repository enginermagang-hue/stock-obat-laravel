@extends('layouts.app')

@section('title', $title)

@section('content')
    <div x-data="masterPage(@js([
            'endpoint' => '/api/v1/master/'.$resource,
            'columns' => $columns,
            'fields' => $fields,
            'defaults' => $defaults,
            'canManage' => $canManage,
        ]))"
         x-init="load();
                 $watch('modalOpen', (value) => {
                     document.documentElement.classList.toggle('os-scroll-locked', value);
                     document.body.classList.toggle('os-scroll-locked', value);
                 })">
        <h1 class="os-page-title">{{ $title }}</h1>
        <p class="os-page-subtitle">Kelola data master. Perubahan dicatat di audit trail.</p>

        <div class="os-alert os-alert--success os-section-tight" x-show="status" x-cloak x-text="status"></div>

        <div class="os-section os-card os-card--flush">
            <div class="os-filter-bar">
                <div class="os-search-wrap">
                    <x-icon name="search-line" class="text-faint" />
                    <input type="text" x-model="search" @input.debounce.300ms="load(1)"
                           placeholder="Cari data..." class="os-search">
                </div>
                @if ($canManage)
                    <button type="button" @click="openCreate()" class="os-btn os-btn--primary ml-auto">
                        <x-icon name="add-line" /> Tambah data
                    </button>
                @endif
            </div>
            <div class="os-table-wrap">
                <table class="os-table os-table--hover">
                    <thead>
                    <tr>
                        @foreach ($columns as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th class="os-td-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="row in rows" :key="row.id">
                        <tr>
                            @foreach (array_keys($columns) as $key)
                                <td x-text="row['display_{{ $key }}']"></td>
                            @endforeach
                            <td class="os-td-right">
                                @if ($canManage)
                                    <div class="inline-block" x-data="{
                                            open: false, top: 0, left: 0,
                                            toggle(event) {
                                                const rect = event.currentTarget.getBoundingClientRect();
                                                this.top = rect.bottom + 6;
                                                this.left = Math.max(8, rect.right - 160);
                                                this.open = ! this.open;
                                            },
                                        }">
                                        <button type="button" class="os-action-trigger" @click="toggle($event)"
                                                aria-label="Aksi data" :aria-expanded="open">
                                            <x-icon name="more-2-fill" />
                                        </button>
                                        <div x-show="open" x-cloak @click.outside="open = false"
                                             :style="`position: fixed; top: ${top}px; left: ${left}px;`"
                                             class="os-dropdown z-50 w-40">
                                            <button type="button" @click="open = false; openEdit(row)"
                                                    class="os-dropdown-item os-dropdown-item--icon">
                                                <x-icon name="edit-line" /> Ubah
                                            </button>
                                            <button type="button" @click="open = false; destroy(row)"
                                                    class="os-dropdown-item os-dropdown-item--icon os-dropdown-item--danger">
                                                <x-icon name="delete-bin-line" /> Hapus
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    </template>
                    <tr x-show="loaded && ! loading && rows.length === 0">
                        <td colspan="{{ count($columns) + 1 }}" class="os-empty">Belum ada data.</td>
                    </tr>
                    <tr x-show="loading">
                        <td colspan="{{ count($columns) + 1 }}" class="os-empty">
                            <span class="inline-flex items-center gap-2">
                                <x-icon name="loader-4-line" class="animate-spin" /> Memuat data...
                            </span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="os-pagination flex items-center justify-between gap-3 px-5 py-4">
                <span class="text-[13px] text-faint" x-text="meta.total ? `${meta.from ?? 0}-${meta.to ?? 0} dari ${meta.total}` : ''"></span>
                <span class="os-toolbar">
                    <button type="button" class="os-btn os-btn--sm os-btn--secondary"
                            :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">
                        Sebelumnya
                    </button>
                    <span class="px-2 text-[13px] text-muted" x-text="`${meta.current_page} / ${meta.last_page}`"></span>
                    <button type="button" class="os-btn os-btn--sm os-btn--secondary"
                            :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">
                        Berikutnya
                    </button>
                </span>
            </div>
        </div>

        @if ($canManage)
            <x-modal show="modalOpen" close="modalOpen = false" size="2xl">
                <x-slot:title>
                    <h2 class="os-card-title" x-text="editingId ? 'Ubah data' : 'Tambah data'">Tambah data</h2>
                    <p class="os-card-subtitle">{{ $title }}</p>
                </x-slot:title>

                <div class="os-form-grid">
                    <template x-for="(field, key) in fields" :key="key">
                        <div>
                            <template x-if="field.type === 'select'">
                                <div class="os-floating">
                                    <select x-model="form[key]" class="os-floating-input os-select">
                                        <option value="">— pilih —</option>
                                        <template x-for="(label, value) in field.options" :key="value">
                                            <option :value="value" x-text="label"></option>
                                        </template>
                                    </select>
                                    <label class="os-floating-label" x-text="field.label"></label>
                                    <span class="os-error" x-show="errors[key]" x-text="errors[key] ? errors[key][0] : ''"></span>
                                </div>
                            </template>
                            <template x-if="field.type === 'textarea'">
                                <div class="os-floating">
                                    <textarea rows="2" x-model="form[key]" class="os-floating-input"></textarea>
                                    <label class="os-floating-label" x-text="field.label"></label>
                                    <span class="os-error" x-show="errors[key]" x-text="errors[key] ? errors[key][0] : ''"></span>
                                </div>
                            </template>
                            <template x-if="field.type === 'checkbox'">
                                <div class="os-field os-field--inline">
                                    <input type="checkbox" x-model="form[key]" class="os-checkbox">
                                    <label class="os-label" x-text="field.label"></label>
                                    <span class="os-error" x-show="errors[key]" x-text="errors[key] ? errors[key][0] : ''"></span>
                                </div>
                            </template>
                            <template x-if="field.type !== 'select' && field.type !== 'textarea' && field.type !== 'checkbox'">
                                <div class="os-floating">
                                    <input :type="field.type === 'number' ? 'number' : (field.type === 'date' ? 'date' : 'text')"
                                           x-model="form[key]" placeholder=" " class="os-floating-input">
                                    <label class="os-floating-label" x-text="field.label"></label>
                                    <span class="os-error" x-show="errors[key]" x-text="errors[key] ? errors[key][0] : ''"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <x-slot:footer>
                    <button type="button" @click="modalOpen = false" class="os-btn os-btn--secondary">
                        <x-icon name="close-line" /> Batal
                    </button>
                    <button type="button" @click="submit()" :disabled="saving" class="os-btn os-btn--primary">
                        <span x-show="! saving" class="inline-flex items-center gap-2">
                            <x-icon name="save-line" /> Simpan
                        </span>
                        <span x-show="saving" x-cloak class="inline-flex items-center gap-2">
                            <x-icon name="loader-4-line" class="animate-spin" /> Menyimpan...
                        </span>
                    </button>
                </x-slot:footer>
            </x-modal>
        @endif
    </div>
@endsection
