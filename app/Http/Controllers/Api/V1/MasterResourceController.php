<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\MasterResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MasterResourceController extends Controller
{
    public function index(Request $request, string $resource): JsonResponse
    {
        $configuration = MasterResource::find($resource);
        Gate::authorize('viewAny', $configuration->model);

        $search = trim((string) $request->query('search', ''));
        $query = ($configuration->model)::query();

        if ($search !== '') {
            $query->where(function ($builder) use ($configuration, $search) {
                foreach ($configuration->searchable as $column) {
                    $builder->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        $rows = $query->orderBy('id')->paginate(10)->withQueryString();

        return response()->json([
            'data' => collect($rows->items())->map(fn ($row) => $configuration->present($row))->all(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'from' => $rows->firstItem(),
                'to' => $rows->lastItem(),
            ],
        ]);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        $configuration = MasterResource::find($resource);
        Gate::authorize('create', $configuration->model);

        $record = new ($configuration->model);
        $record->fill($this->validated($request, $configuration))->save();

        return response()->json(['data' => $configuration->present($record)], 201);
    }

    public function update(Request $request, string $resource, int $id): JsonResponse
    {
        $configuration = MasterResource::find($resource);
        $record = ($configuration->model)::query()->findOrFail($id);
        Gate::authorize('update', $record);

        $record->fill($this->validated($request, $configuration, $record))->save();

        return response()->json(['data' => $configuration->present($record)]);
    }

    public function destroy(string $resource, int $id): JsonResponse
    {
        $configuration = MasterResource::find($resource);
        $record = ($configuration->model)::query()->findOrFail($id);
        Gate::authorize('delete', $record);

        $record->delete();

        return response()->json(['deleted' => true]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, MasterResource $configuration, ?Model $record = null): array
    {
        $data = $request->validate($configuration->rules($record));

        foreach ($configuration->fields as $key => $field) {
            if (($field['type'] ?? 'text') === 'checkbox') {
                $data[$key] = (bool) ($data[$key] ?? false);
            } elseif (($field['nullable'] ?? false) && ($data[$key] ?? '') === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }
}
