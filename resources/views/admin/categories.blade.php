@extends('admin.layout')

@section('title', 'Categories')

@section('page_title', 'Manage categories')

@section('content')
<p class="page-hint">
    Products must belong to a category. Create categories here before adding products (e.g. Grains, Vegetables, Oils).
</p>

<div class="card">
    <form method="post" action="{{ route('admin.categories.store') }}" class="form-grid">
        @csrf
        <div>
            <label>Name</label>
            <input name="name" placeholder="Grains" required>
        </div>
        <div>
            <label>Icon (optional)</label>
            <input name="icon" placeholder="wheat / leaf / bottle">
        </div>
        <div>
            <label>&nbsp;</label>
            <button class="btn btn-primary" type="submit">Add category</button>
        </div>
    </form>

    <div style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Icon</th>
                <th>Products</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($categories as $category)
                <tr>
                    <form method="post" action="{{ route('admin.categories.update', $category->id) }}">
                        @csrf
                        <td style="min-width:160px;"><input name="name" value="{{ $category->name }}" required></td>
                        <td style="color:var(--muted);font-size:12px;">{{ $category->slug }}</td>
                        <td style="min-width:120px;"><input name="icon" value="{{ $category->icon }}"></td>
                        <td>{{ (int) $category->products_count }}</td>
                        <td style="white-space:nowrap;">
                            <button class="btn" type="submit">Save</button>
                        </td>
                    </form>
                </tr>
                <tr>
                    <td colspan="5" style="padding-top:0;border-bottom:1px solid var(--border);">
                        @if ((int) $category->products_count === 0)
                            <form method="post" action="{{ route('admin.categories.destroy', $category->id) }}" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                                @csrf
                                <button class="btn" type="submit" style="margin-bottom:10px;">Delete</button>
                            </form>
                        @else
                            <span style="color:var(--muted);font-size:12px;">Delete disabled while products exist.</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            @if ($categories->isEmpty())
                <tr><td colspan="5" style="color:var(--muted);">No categories yet. Add one above.</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
