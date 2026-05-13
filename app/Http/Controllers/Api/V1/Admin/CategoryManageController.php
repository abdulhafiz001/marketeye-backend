<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryManageController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $rows = Category::query()->orderBy('name')->get()->map(fn (Category $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'icon' => $c->icon,
        ]);

        return $this->success(['categories' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:64'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $cat = Category::query()->create($data);

        return $this->success(['category' => ['id' => $cat->id]], 'Created.', 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $cat = Category::query()->find($id);
        if (! $cat) {
            return $this->failure('Not found.', [], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:64'],
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $cat->update($data);

        return $this->success(['category' => ['id' => $cat->id]]);
    }

    public function destroy(int $id): JsonResponse
    {
        $cat = Category::query()->find($id);
        if (! $cat) {
            return $this->failure('Not found.', [], 404);
        }

        if ($cat->products()->exists()) {
            return $this->failure('Category has products.', [], 422);
        }

        $cat->delete();

        return $this->success(null, 'Deleted.');
    }
}
