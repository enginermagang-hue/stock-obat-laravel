<?php

namespace App\Http\Controllers;

use App\Support\MasterResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MasterPageController extends Controller
{
    public function __invoke(string $resource): View
    {
        $configuration = MasterResource::find($resource);
        Gate::authorize('viewAny', $configuration->model);

        return view('pages.master.index', [
            'title' => $configuration->title,
            'resource' => $configuration->key,
            'columns' => $configuration->columns,
            'fields' => $configuration->resolvedFields(),
            'defaults' => $configuration->defaults(),
            'canManage' => auth()->user()->can('manage-master'),
        ]);
    }
}
