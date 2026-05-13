<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductManageController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $rows = Product::query()->with('category')->orderBy('name')->get()->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'unit' => $p->unit,
            'description' => $p->description,
            'image' => $p->image,
            'is_active' => (bool) $p->is_active,
            'category_id' => $p->category_id,
            'category' => $p->category ? [
                'id' => $p->category->id,
                'name' => $p->category->name,
                'slug' => $p->category->slug,
            ] : null,
        ]);

        return $this->success(['products' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']).'-tmp';
        $product = Product::query()->create($data);
        $product->update(['slug' => Str::slug($data['name']).'-'.$product->id]);

        return $this->success(['product' => ['id' => $product->id]], 'Created.', 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $product = Product::query()->find($id);
        if (! $product) {
            return $this->failure('Not found.', [], 404);
        }

        $data = $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'unit' => ['sometimes', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']).'-'.$product->id;
        }

        $product->update($data);

        return $this->success(['product' => ['id' => $product->id]]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::query()->find($id);
        if (! $product) {
            return $this->failure('Not found.', [], 404);
        }

        $product->update(['is_active' => false]);

        return $this->success(null, 'Product deactivated.');
    }
}
