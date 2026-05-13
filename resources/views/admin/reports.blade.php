@extends('admin.layout')

@section('title', 'Reports')

@section('page_title', 'Reports')

@push('styles')
<style>
    .report-grid { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
    @media (max-width: 900px) { .report-grid { grid-template-columns: 1fr; } }
    .metric-label { color: var(--muted); font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; }
    .metric-value { margin-top: 8px; font-size: 24px; font-weight: 900; }
</style>
@endpush

@section('content')
<p class="page-hint">Weekly product movement based on average prices from the last 7 days compared with the previous 7 days.</p>

<div class="report-grid">
    <div class="card">
        <div class="metric-label">Biggest weekly increase</div>
        <div class="metric-value">{{ $biggestIncrease['name'] ?? 'No data yet' }}</div>
        @if ($biggestIncrease)
            <div style="color:#FCA5A5;font-weight:900;margin-top:6px;">+{{ $biggestIncrease['change_percent'] }}%</div>
        @endif
    </div>
    <div class="card">
        <div class="metric-label">Current avg</div>
        <div class="metric-value">₦{{ $biggestIncrease ? number_format((float) $biggestIncrease['current_avg'], 2) : '0.00' }}</div>
    </div>
    <div class="card">
        <div class="metric-label">Previous avg</div>
        <div class="metric-value">₦{{ $biggestIncrease ? number_format((float) $biggestIncrease['previous_avg'], 2) : '0.00' }}</div>
    </div>
</div>

<div class="card">
    <div style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th>Product</th>
                <th>Unit</th>
                <th>This week avg</th>
                <th>Previous week avg</th>
                <th>Change</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($weeklyMovers as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['unit'] }}</td>
                    <td>₦{{ number_format((float) $row['current_avg'], 2) }}</td>
                    <td>₦{{ number_format((float) $row['previous_avg'], 2) }}</td>
                    <td style="font-weight:900;color:{{ $row['change_percent'] >= 0 ? '#FCA5A5' : '#86EFAC' }};">
                        {{ $row['change_percent'] > 0 ? '+' : '' }}{{ $row['change_percent'] }}%
                    </td>
                </tr>
            @endforeach
            @if ($weeklyMovers->isEmpty())
                <tr><td colspan="5" style="color:var(--muted);">No weekly price data yet.</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
