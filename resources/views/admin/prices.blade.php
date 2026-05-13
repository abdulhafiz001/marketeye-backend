@extends('admin.layout')

@section('title', 'Prices')

@section('page_title', 'Manage prices')

@push('styles')
<style>
    .price-form { grid-template-columns: minmax(220px, 1.2fr) minmax(280px, 1.7fr) minmax(150px, .8fr) minmax(150px, .8fr) auto; align-items:start; }
    .market-picker {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        max-height: 220px;
        overflow: auto;
        padding: 4px;
        border: 1px solid var(--border);
        border-radius: 14px;
        background: rgba(11,18,32,.55);
    }
    .market-option {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 44px;
        padding: 10px 12px;
        margin: 0;
        border: 1px solid rgba(148,163,184,.16);
        border-radius: 12px;
        background: rgba(15,23,42,.75);
        color: var(--text);
        cursor: pointer;
        text-transform: none;
        letter-spacing: 0;
        font-size: 13px;
        font-weight: 800;
    }
    .market-option:hover { border-color: rgba(34,197,94,.4); background: rgba(34,197,94,.08); }
    .market-option input {
        width: 18px;
        height: 18px;
        accent-color: #22C55E;
        flex: 0 0 auto;
    }
    .market-option span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .price-form-actions { display: flex; align-items: end; height: 100%; }
    .inline-price-form {
        display: grid;
        grid-template-columns: minmax(110px, 1fr) auto;
        gap: 8px;
        min-width: 230px;
        align-items: center;
    }
    .inline-price-form input { min-width: 0; }
    @media (max-width: 1100px) {
        .price-form { grid-template-columns: 1fr 1fr; }
        .market-picker { grid-template-columns: 1fr; }
        .price-form-actions { height: auto; }
    }
    @media (max-width: 760px) {
        .price-form { grid-template-columns: 1fr; }
        .market-picker { max-height: 260px; }
    }
</style>
@endpush

@section('content')
<p class="page-hint">Set or update the price for each product in each market. These prices appear in the app as market snapshots.</p>

<div class="card" style="margin-bottom: 16px;">
    <form method="post" action="{{ route('admin.prices.store') }}" class="form-grid price-form">
        @csrf
        <div>
            <label>Product</label>
            <select name="product_id" required>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->unit }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Markets (select one or more)</label>
            <div class="market-picker">
                @foreach ($markets as $market)
                    <label class="market-option">
                        <input type="checkbox" name="market_ids[]" value="{{ $market->id }}">
                        <span>{{ $market->name }}</span>
                    </label>
                @endforeach
            </div>
            <small style="color:var(--muted);">Saving applies the same price and date to every market you tick.</small>
        </div>
        <div>
            <label>Price</label>
            <input name="price" type="number" min="1" step="0.01" placeholder="3500" required>
        </div>
        <div>
            <label>Date</label>
            <input name="effective_date" type="date" value="{{ now()->toDateString() }}" required>
        </div>
        <div class="price-form-actions">
            <button class="btn btn-primary" type="submit">Save price</button>
        </div>
    </form>
</div>

<div class="card">
    <div style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th>Product</th>
                <th>Market</th>
                <th>Average</th>
                <th>Low</th>
                <th>High</th>
                <th>Submissions</th>
                <th>Source</th>
                <th>Date</th>
                <th>Update</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($snapshots as $snapshot)
                <tr>
                    <td>{{ $snapshot->product?->name }}<div style="color:var(--muted);font-size:11px;">{{ $snapshot->product?->unit }}</div></td>
                    <td>{{ $snapshot->market?->name }}</td>
                    <td>₦{{ number_format((float) $snapshot->avg_price, 2) }}</td>
                    <td>₦{{ number_format((float) $snapshot->min_price, 2) }}</td>
                    <td>₦{{ number_format((float) $snapshot->max_price, 2) }}</td>
                    <td>{{ $snapshot->submission_count }}</td>
                    <td><span class="pill">{{ str_replace('_', ' ', $snapshot->snapshot_source) }}</span></td>
                    <td>{{ $snapshot->snapshot_date?->toDateString() }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.prices.store') }}" class="inline-price-form">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $snapshot->product_id }}">
                            <input type="hidden" name="market_id" value="{{ $snapshot->market_id }}">
                            <input type="hidden" name="effective_date" value="{{ now()->toDateString() }}">
                            <input name="price" type="number" min="1" step="0.01" value="{{ (float) $snapshot->avg_price }}" required>
                            <button class="btn" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            @if ($snapshots->isEmpty())
                <tr><td colspan="9" style="color:var(--muted);">No prices yet.</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
