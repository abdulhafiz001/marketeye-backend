@extends('admin.layout')

@section('title', 'Product suggestions')
@section('page_title', 'User-added products & suggestions')

@section('content')
<p class="page-hint">
    New products submitted by users who meet location & validation requirements go live immediately in the catalog and create price submissions in <strong>Manage submissions</strong>.
    You can review them here at any time or reject/deactivate any invalid entry.
</p>

@if ($suggestions->isEmpty())
    <div class="card"><p>No user product submissions recorded yet.</p></div>
@endif

@foreach ($suggestions as $suggestion)
    <div class="card" style="margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                    <h2 style="margin:0;font-size:17px;">{{ $suggestion->product_name }}</h2>
                    <span class="pill" style="{{ $suggestion->status === 'approved' ? 'border-color:rgba(34,197,94,.4);color:#86EFAC;background:rgba(34,197,94,.12);' : ($suggestion->status === 'pending' ? 'border-color:rgba(245,158,11,.45);color:#FDE68A;background:rgba(245,158,11,.12);' : 'border-color:rgba(239,68,68,.4);color:#FCA5A5;') }}">
                        {{ $suggestion->status === 'approved' ? '✓ Live in App' : ucfirst($suggestion->status) }}
                    </span>
                </div>
                <p style="margin:0;color:#94A3B8;">Category: <strong>{{ $suggestion->category?->name ?? $suggestion->category_name }}</strong> · Unit: <strong>{{ $suggestion->unit }}</strong></p>
                <p style="margin:6px 0;color:#94A3B8;">Submitted by {{ $suggestion->user?->name ?: ($suggestion->user?->email ?? 'User') }} · {{ $suggestion->created_at?->format('M j, Y H:i') }}</p>
                @if ($suggestion->reason)<p style="margin:4px 0 0;">Note: {{ $suggestion->reason }}</p>@endif
            </div>
            <div style="display:flex;gap:8px;">
                @if ($suggestion->status === 'pending')
                    <form method="post" action="{{ route('admin.catalog-suggestions.approve', $suggestion->id) }}">
                        @csrf<button class="btn btn-primary" type="submit">Publish to App</button>
                    </form>
                @endif
                @if ($suggestion->status !== 'rejected')
                    <form method="post" action="{{ route('admin.catalog-suggestions.reject', $suggestion->id) }}" onsubmit="return confirm('Deactivate and reject this user-submitted product?');">
                        @csrf<button class="btn" type="submit">Reject / Deactivate</button>
                    </form>
                @endif
            </div>
        </div>
        <div style="overflow:auto;margin-top:12px;">
            <table>
                <thead>
                    <tr>
                        <th>Market</th>
                        <th>Price (₦ per {{ $suggestion->unit }})</th>
                        <th>GPS coordinates</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($suggestion->market_prices as $row)
                    @php($market = \App\Models\Market::query()->find($row['market_id'] ?? null))
                    <tr>
                        <td>{{ $market?->name ?? 'Unknown market' }}</td>
                        <td>₦{{ number_format((float) ($row['price'] ?? 0), 2) }}</td>
                        <td>{{ $row['latitude'] ?? '—' }}, {{ $row['longitude'] ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach
@endsection
