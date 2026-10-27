@extends('admin.layout')

@section('title', 'Product suggestions')
@section('page_title', 'Review product suggestions')

@section('content')
<p class="page-hint">New products, categories, and GPS checked market prices stay out of the live catalog until reviewed. Approving adds the product; its submitted prices go to the normal price review queue.</p>

@if ($suggestions->isEmpty())
    <div class="card"><p>No product suggestions are waiting for review.</p></div>
@endif

@foreach ($suggestions as $suggestion)
    <div class="card" style="margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin:0 0 6px;">{{ $suggestion->product_name }}</h2>
                <p style="margin:0;color:#94A3B8;">Category: {{ $suggestion->category?->name ?? $suggestion->category_name }} · Unit: {{ $suggestion->unit }}</p>
                <p style="margin:6px 0;color:#94A3B8;">Suggested by {{ $suggestion->user?->name ?? 'Deleted user' }} · {{ $suggestion->created_at?->format('M j, Y H:i') }}</p>
                @if ($suggestion->reason)<p>{{ $suggestion->reason }}</p>@endif
            </div>
            <div style="display:flex;gap:8px;">
                <form method="post" action="{{ route('admin.catalog-suggestions.approve', $suggestion->id) }}" onsubmit="return confirm('Add this product to the live catalog? Its prices will remain pending price review.');">
                    @csrf<button class="btn btn-primary" type="submit">Approve product</button>
                </form>
                <form method="post" action="{{ route('admin.catalog-suggestions.reject', $suggestion->id) }}" onsubmit="return confirm('Reject this product suggestion?');">
                    @csrf<button class="btn" type="submit">Reject</button>
                </form>
            </div>
        </div>
        <div style="overflow:auto;margin-top:12px;">
            <table><thead><tr><th>Market</th><th>Price (₦ per {{ $suggestion->unit }})</th><th>GPS coordinates</th></tr></thead><tbody>
            @foreach ($suggestion->market_prices as $row)
                @php($market = \App\Models\Market::query()->find($row['market_id'] ?? null))
                <tr>
                    <td>{{ $market?->name ?? 'Unknown market' }}</td>
                    <td>{{ number_format((float) ($row['price'] ?? 0), 2) }}</td>
                    <td>{{ $row['latitude'] ?? '—' }}, {{ $row['longitude'] ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody></table>
        </div>
    </div>
@endforeach
@endsection
