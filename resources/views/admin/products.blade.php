@extends('admin.layout')

@section('title', 'Products')

@section('page_title', 'Manage products')

@section('content')
<p class="page-hint">
    Add new items or update names, categories, units, and visibility.
    Need a category first? Go to <a href="{{ route('admin.categories.index') }}" style="color:#86EFAC;">Manage categories</a>.
</p>

@if ($categories->isEmpty())
    <div class="err">No categories yet. Create one under Manage categories before adding products.</div>
@endif

<div class="card">
    <form method="post" action="{{ route('admin.products.store') }}" class="form-grid">
        @csrf
        <div>
            <label>Name</label>
            <input name="name" placeholder="Rice" required @disabled($categories->isEmpty())>
        </div>
        <div>
            <label>Category</label>
            <select name="category_id" required @disabled($categories->isEmpty())>
                @forelse ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @empty
                    <option value="">No categories</option>
                @endforelse
            </select>
        </div>
        <div>
            <label>Unit</label>
            <input name="unit" placeholder="modu" required>
        </div>
        <div>
            <label>&nbsp;</label>
            <button class="btn btn-primary" type="submit">Add product</button>
        </div>
    </form>

    <div style="overflow:auto;">
        <table>
            <thead><tr><th>Name</th><th>Category</th><th>Unit</th><th>Active</th><th></th></tr></thead>
            <tbody>
            @foreach ($products as $product)
                <tr>
                    <form method="post" action="{{ route('admin.products.update', $product->id) }}">
                        @csrf
                        <td style="min-width:160px;"><input name="name" value="{{ $product->name }}" required></td>
                        <td style="min-width:140px;">
                            <select name="category_id" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($product->category_id === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td style="min-width:120px;"><input name="unit" value="{{ $product->unit }}" required></td>
                        <td style="width:72px;"><input type="checkbox" name="is_active" value="1" @checked($product->is_active)></td>
                        <td style="white-space:nowrap;"><button class="btn" type="submit">Save</button></td>
                    </form>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
