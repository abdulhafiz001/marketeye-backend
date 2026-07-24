@extends('admin.layout')

@section('title', 'External price data')

@section('page_title', 'External price data')

@section('content')
<p class="page-hint">
    Generate review items for markets/products with stale or missing crowd prices. Nothing appears in the app until an admin approves it.
    Live prices come from community submissions and manual admin entry — not third-party catalog URLs.
</p>

<div class="card" style="margin-bottom: 16px;">
    <form method="post" action="{{ route('admin.external.pull') }}" class="form-grid">
        @csrf
        <div>
            <label>&nbsp;</label>
            <button class="btn btn-primary" type="submit">Generate stale-price review items</button>
        </div>
    </form>
</div>

<div class="card">
    <div style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th>Source</th>
                <th>Product</th>
                <th>Market</th>
                <th>Price</th>
                <th>Date</th>
                <th>Status</th>
                <th>Approved by</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($seeds as $seed)
                <tr>
                    <td>{{ strtoupper($seed->source) }}</td>
                    <td>{{ $seed->product?->name }}</td>
                    <td>{{ $seed->market?->name }}</td>
                    <td>₦{{ number_format((float) $seed->normalized_price, 2) }}</td>
                    <td>{{ $seed->effective_date?->toDateString() }}</td>
                    <td>
                        <span class="pill">{{ $seed->status }}</span>
                        @if ($seed->error_message)
                            <div style="color:#FCA5A5;font-size:11px;margin-top:4px;">{{ $seed->error_message }}</div>
                        @endif
                    </td>
                    <td>{{ $seed->approver?->email ?? '—' }}</td>
                    <td>
                        @if ($seed->status === 'pending')
                            <form method="post" action="{{ route('admin.external.approve', $seed->id) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit">Approve</button>
                            </form>
                        @else
                            <span style="color:var(--muted);font-size:12px;">—</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            @if ($seeds->isEmpty())
                <tr><td colspan="8" style="color:var(--muted);">No review items yet. Generate stale-price review items above.</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
