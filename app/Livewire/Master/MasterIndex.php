<?php

namespace App\Livewire\Master;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

abstract class MasterIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public array $form = [];
    public ?int $editingId = null;

    abstract protected function modelClass(): string;

    abstract protected function title(): string;

    /** @return array<string, string> */
    protected function columns(): array
    {
        return [];
    }

    /** @return array<string, array<string, mixed>> */
    protected function fields(): array
    {
        return [];
    }

    public function mount(): void
    {
        $this->search = (string) request('search', '');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $form = [];

        foreach ($this->fields() as $key => $field) {
            $form[$key] = $field['default'] ?? ((($field['type'] ?? 'text') === 'checkbox') ? false : '');
        }

        $this->form = $form;
        $this->editingId = null;
    }

    public function edit(int $id): void
    {
        $modelClass = $this->modelClass();
        $record = $modelClass::findOrFail($id);
        Gate::authorize('update', $record);

        foreach (array_keys($this->fields()) as $key) {
            $value = $record->getAttribute($key);
            $this->form[$key] = $value instanceof \BackedEnum ? $value->value : $value;
        }

        $this->editingId = $id;
    }

    public function save(): void
    {
        $modelClass = $this->modelClass();
        $record = $this->editingId
            ? $modelClass::findOrFail($this->editingId)
            : new $modelClass;

        Gate::authorize($this->editingId ? 'update' : 'create', $record);

        $rules = [];

        foreach ($this->fields() as $key => $field) {
            $rules['form.'.$key] = $field['rules'] ?? 'nullable';
        }

        $data = $this->validate($rules)['form'];

        foreach ($this->fields() as $key => $field) {
            if (($field['type'] ?? 'text') === 'checkbox') {
                $data[$key] = (bool) ($data[$key] ?? false);
            } elseif (($field['nullable'] ?? false) && ($data[$key] ?? '') === '') {
                $data[$key] = null;
            }
        }

        $record->fill($data)->save();
        $this->resetForm();
        session()->flash('status', 'Data tersimpan.');
    }

    public function delete(int $id): void
    {
        $modelClass = $this->modelClass();
        $record = $modelClass::findOrFail($id);
        Gate::authorize('delete', $record);
        $record->delete();
        $this->resetForm();
        session()->flash('status', 'Data dihapus.');
    }

    public function render()
    {
        $modelClass = $this->modelClass();
        $query = $modelClass::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                foreach (array_keys($this->columns()) as $column) {
                    $q->orWhere($column, 'like', '%'.$this->search.'%');
                }
            });
        }

        /** @var LengthAwarePaginator $rows */
        $rows = $query->orderBy('id')->paginate(10);

        return view('livewire.master.master-index', [
            'rows' => $rows,
            'columns' => $this->columns(),
            'fields' => $this->fields(),
            'title' => $this->title(),
        ])->layout('layouts.app', ['title' => $this->title()]);
    }
}
