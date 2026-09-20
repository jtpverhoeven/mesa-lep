<?php

namespace App\Http\Controllers;

use App\Models\ProductGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductGroupController extends Controller
{
    public function index(): View
    {
        return view('portal.product-groups');
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:25', 'max:250'],
            'search' => ['nullable', 'string', 'max:100'],
            'hide_default' => ['nullable', 'boolean'],
        ]);
        $search = trim($filters['search'] ?? '');
        $groups = ProductGroup::query()
            ->with('client:id,name')
            ->when($filters['hide_default'] ?? false, fn (Builder $query) => $query->where('default', '!=', 1))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'ilike', '%'.$search.'%')
                ->orWhereHas('client', fn (Builder $query) => $query->where('name', 'ilike', '%'.$search.'%'))))
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 100);

        return response()->json([
            'data' => $groups->getCollection()->map(fn (ProductGroup $group): array => [
                'id' => $group->id,
                'portal_id' => $group->portal_id,
                'name' => $group->name,
                'client' => $group->client?->name ?? '-',
                'default' => (bool) $group->default,
                'visible' => (bool) $group->visible,
            ])->values(),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ],
        ]);
    }
}
