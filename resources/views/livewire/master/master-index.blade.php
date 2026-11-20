<div>
    <h1 class="text-xl font-semibold">{{ $title }}</h1>
    <p class="mt-1 text-sm text-slate-500">Kelola data master. Perubahan dicatat di audit trail.</p>

    @if (session('status'))
        <div class="mt-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-5 rounded-lg border border-slate-200 bg-white">
        <div class="flex items-center gap-3 border-b border-slate-200 p-4">
            <input type="text" wire:model.live="search" placeholder="Cari data..."
                   class="w-64 rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100 text-xs uppercase text-slate-500">
                <tr>
                    @foreach ($columns as $label)
                        <th class="px-4 py-2">{{ $label }}</th>
                    @endforeach
                    <th class="px-4 py-2"></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr class="border-t border-slate-100">
                        @foreach (array_keys($columns) as $key)
                            <td class="px-4 py-2">{{ is_bool($row->{$key}) ? ($row->{$key} ? 'Ya' : 'Tidak') : $row->{$key} }}</td>
                        @endforeach
                        <td class="px-4 py-2 text-right">
                            @can('manage-master')
                                <button wire:click="edit({{ $row->id }})" class="mr-2 text-xs text-sky-700">Edit</button>
                                <button wire:click="delete({{ $row->id }})" wire:confirm="Hapus data ini?"
                                        class="text-xs text-red-600">Hapus</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) + 1 }}" class="px-4 py-6 text-center text-slate-400">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $rows->links() }}</div>
    </div>

    @can('manage-master')
        <div class="mt-5 rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-semibold">{{ $editingId ? 'Ubah data' : 'Tambah data' }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($fields as $key => $field)
                    <div class="{{ ($field['type'] ?? 'text') === 'checkbox' ? 'flex items-center gap-2' : '' }}">
                        @if (($field['type'] ?? 'text') === 'checkbox')
                            <input type="checkbox" id="field-{{ $key }}" wire:model="form.{{ $key }}"
                                   class="rounded border-slate-300">
                            <label for="field-{{ $key }}" class="text-xs font-semibold">{{ $field['label'] }}</label>
                        @else
                            <label for="field-{{ $key }}" class="mb-1 block text-xs font-semibold">{{ $field['label'] }}</label>
                            @if (($field['type'] ?? 'text') === 'select')
                                <select id="field-{{ $key }}" wire:model="form.{{ $key }}"
                                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                    <option value="">— pilih —</option>
                                    @foreach ($field['options'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input id="field-{{ $key }}"
                                       type="{{ ($field['type'] ?? 'text') === 'number' ? 'number' : 'text' }}"
                                       wire:model="form.{{ $key }}"
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            @endif
                        @endif
                        @error('form.'.$key) <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                @endforeach
            </div>
            <div class="mt-5 flex gap-2">
                <button wire:click="save" class="rounded-md bg-slate-800 px-4 py-2 text-sm text-white">Simpan</button>
                <button wire:click="resetForm" class="rounded-md border border-slate-300 px-4 py-2 text-sm">Batal</button>
            </div>
        </div>
    @endcan
</div>
