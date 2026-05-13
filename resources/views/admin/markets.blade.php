@extends('admin.layout')

@section('title', 'Markets')

@section('page_title', 'Manage markets')

@section('content')
<p class="page-hint">Create markets or edit names, areas, and whether they appear in the app.</p>

<div class="card">
    <form method="post" action="{{ route('admin.markets.store') }}" class="form-grid">
        @csrf
        <div><label>Name</label><input name="name" placeholder="Wuse Market" required></div>
        <div><label>Area</label><input name="area" placeholder="Wuse"></div>
        <div><label>City</label><input name="city" value="Abuja"></div>
        <div>
            <label>&nbsp;</label>
            <button class="btn btn-primary" type="submit">Add market</button>
        </div>
    </form>

    <div style="overflow:auto;">
        <table>
            <thead><tr><th>Name</th><th>Area</th><th>Active</th><th></th></tr></thead>
            <tbody>
            @foreach ($markets as $market)
                <tr>
                    <form method="post" action="{{ route('admin.markets.update', $market->id) }}">
                        @csrf
                        <td><input name="name" value="{{ $market->name }}" required></td>
                        <td><input name="area" value="{{ $market->area }}"></td>
                        <td style="width:72px;"><input type="checkbox" name="is_active" value="1" @checked($market->is_active)></td>
                        <td><button class="btn" type="submit">Save</button></td>
                    </form>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
