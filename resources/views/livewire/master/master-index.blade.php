<div>
    <h1 class="os-page-title">{{ $title }}</h1>
    <p class="os-page-subtitle">Kelola data master. Perubahan dicatat di audit trail.</p>

    @if (session('status'))
        <div class="os-alert os-alert--success os-section-tight">
            {{ session('status') }}
        </div>
    @endif

    <div class="os-section os-card os-card--flush">
        <div class="os-filter-bar">
            <div class="os-search-wrap">
                <x-icon name="search-line" :size="18" class="text-faint" />
                <input type="text" wire:model.live="search" placeholder="Cari data..." class="os-search">
            </div>
        </div>
        <div class="os-table-wrap">
            <table class="os-table os-table--striped os-table--hover">
                <thead>
                <tr>
                    @foreach ($columns as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach (array_keys($columns) as $key)
                            <td>{{ is_bool($row->{$key}) ? ($row->{$key} ? 'Ya' : 'Tidak') : $row->{$key} }}</td>
                        @endforeach
                        <td class="os-td-right">
                            @can('manage-master')
                                <div class="os-table-actions">
                                    <button wire:click="edit({{ $row->id }})" class="os-btn os-btn--sm os-btn--ghost">
                                        <x-icon name="edit-line" :size="14" /> Edit
                                    </button>
                                    <button wire:click="delete({{ $row->id }})" wire:confirm="Hapus data ini?"
                                            class="os-btn os-btn--sm os-btn--danger">
                                        <x-icon name="delete-bin-line" :size="14" /> Hapus
                                    </button>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) + 1 }}" class="os-empty">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="os-pagination">{{ $rows->links() }}</div>
    </div>

    @can('manage-master')
        <div class="os-section os-card os-card-body">
            <h2 class="os-card-title">{{ $editingId ? 'Ubah data' : 'Tambah data' }}</h2>
            <div class="os-section-tight os-form-grid">
                @foreach ($fields as $key => $field)
                    @php $type = $field['type'] ?? 'text'; @endphp
                    @if ($type === 'checkbox')
                        <div class="os-field os-field--inline">
                            <input type="checkbox" id="field-{{ $key }}" wire:model="form.{{ $key }}"
                                   class="os-checkbox">
                            <label for="field-{{ $key }}" class="os-label">{{ $field['label'] }}</label>
                            @error('form.'.$key) <span class="os-error">{{ $message }}</span> @enderror
                        </div>
                    @elseif ($type === 'select')
                        <div class="os-floating">
                            <select id="field-{{ $key }}" wire:model="form.{{ $key }}"
                                    class="os-floating-input os-select">
                                <option value="">— pilih —</option>
                                @foreach ($field['options'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <label class="os-floating-label" for="field-{{ $key }}">{{ $field['label'] }}</label>
                            @error('form.'.$key) <span class="os-error">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <div class="os-floating">
                            <input id="field-{{ $key }}"
                                   type="{{ $type === 'number' ? 'number' : 'text' }}"
                                   placeholder=" "
                                   wire:model="form.{{ $key }}"
                                   class="os-floating-input">
                            <label class="os-floating-label" for="field-{{ $key }}">{{ $field['label'] }}</label>
                            @error('form.'.$key) <span class="os-error">{{ $message }}</span> @enderror
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="os-section os-toolbar">
                <button wire:click="save" class="os-btn os-btn--primary">
                    <x-icon name="save-line" :size="16" /> Simpan
                </button>
                <button wire:click="resetForm" class="os-btn os-btn--secondary">
                    <x-icon name="close-line" :size="16" /> Batal
                </button>
            </div>
        </div>
    @endcan
</div>
