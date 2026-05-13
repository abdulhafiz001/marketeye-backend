@extends('admin.layout')

@section('title', 'Dashboard')

@section('page_title', 'Dashboard')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
@endpush

@push('styles')
<style>
    .dash-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; margin-bottom: 14px; }
    @media (max-width: 1100px) { .dash-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .dash-card .k { color: var(--muted); font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .dash-card .v { margin-top: 8px; font-size: 26px; font-weight: 900; letter-spacing: -0.03em; }
    .row2 { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 14px; margin-top: 14px; }
    @media (max-width: 900px) { .row2 { grid-template-columns: 1fr; } }
    .row2 canvas { width: 100% !important; height: 280px !important; }
    .quick-links { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
    .quick-links a { display: inline-flex; align-items: center; padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); color: var(--muted); font-weight: 800; font-size: 12px; text-decoration: none; }
    .quick-links a:hover { color: var(--text); border-color: rgba(245,158,11,0.4); }
</style>
@endpush

@section('content')
<p class="page-hint">Overview of submissions, markets, and product coverage. Trader count includes only accounts with the user role.</p>

<div class="dash-grid">
    <div class="card dash-card">
        <div class="k">Traders</div>
        <div class="v">{{ $stats['trader_users'] }}</div>
    </div>
    <div class="card dash-card">
        <div class="k">Submissions today</div>
        <div class="v">{{ $stats['submissions_today'] }}</div>
    </div>
    <div class="card dash-card">
        <div class="k">Pending approvals</div>
        <div class="v">{{ $stats['pending_approvals'] }}</div>
    </div>
    <div class="card dash-card">
        <div class="k">Active markets</div>
        <div class="v">{{ $stats['active_markets'] }}</div>
    </div>
    <div class="card dash-card">
        <div class="k">Products</div>
        <div class="v">{{ $stats['products_count'] }}</div>
    </div>
</div>

<div class="card" style="margin-bottom: 14px;">
    <div class="k" style="font-size: 11px;">Price change % (this week vs prior week)</div>
    <div style="margin-top: 8px; font-size: 20px; font-weight: 900;">{{ $stats['price_change_percent_this_week'] }}%</div>
</div>

<div class="row2">
    <div class="card">
        <div style="margin-bottom: 12px; color: var(--muted); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Submission volume (30 days)</div>
        <canvas id="submissionsChart"></canvas>
    </div>
    <div class="card">
        <div style="margin-bottom: 12px; color: var(--muted); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Avg price by category (this week)</div>
        <canvas id="categoryChart"></canvas>
    </div>
</div>

<div class="card" style="margin-top: 14px;">
    <div style="margin-bottom: 12px; color: var(--muted); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Recent pending submissions</div>
    <div style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th>Product</th>
                <th>Market</th>
                <th>Price</th>
                <th>User</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($recentPending as $s)
                <tr>
                    <td>{{ $s->product?->name }}</td>
                    <td>{{ $s->market?->name }}</td>
                    <td>
                        ₦{{ number_format((float) $s->price, 2) }}
                        <div style="color:var(--muted);font-size:11px;">
                            {{ rtrim(rtrim(number_format((float) ($s->quantity_value ?? 1), 3), '0'), '.') }} {{ $s->quantity_unit ?: $s->product?->unit }}
                        </div>
                    </td>
                    <td>{{ $s->user?->email ?? '—' }}</td>
                    <td><span class="pill">{{ $s->status }}</span></td>
                </tr>
            @endforeach
            @if ($recentPending->isEmpty())
                <tr><td colspan="5" style="color:var(--muted);">No pending submissions.</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<div class="quick-links">
    <a href="{{ route('admin.submissions.index') }}">Review all submissions →</a>
    <a href="{{ route('admin.products.index') }}">Manage products →</a>
    <a href="{{ route('admin.markets.index') }}">Manage markets →</a>
</div>
@endsection

@push('scripts')
<script>
    const submissionLabels = @json($submissionsChart['labels']);
    const submissionCounts = @json($submissionsChart['values']);
    const catLabels = @json($categoryChart['labels']);
    const catAvgs = @json($categoryChart['values']);

    const chartText = '#94A3B8';
    const chartGrid = 'rgba(148, 163, 184, 0.15)';

    new Chart(document.getElementById('submissionsChart'), {
        type: 'line',
        data: {
            labels: submissionLabels,
            datasets: [{
                label: 'Submissions',
                data: submissionCounts,
                borderColor: '#22C55E',
                backgroundColor: 'rgba(34, 197, 94, 0.15)',
                fill: true,
                tension: 0.25,
            }]
        },
        options: {
            plugins: { legend: { labels: { color: '#CBD5E1' } } },
            scales: {
                x: { ticks: { color: chartText }, grid: { color: chartGrid } },
                y: { ticks: { color: chartText }, grid: { color: chartGrid } },
            }
        }
    });

    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: catLabels,
            datasets: [{
                label: 'Avg ₦',
                data: catAvgs,
                backgroundColor: 'rgba(245, 158, 11, 0.55)',
                borderColor: 'rgba(245, 158, 11, 0.9)',
                borderWidth: 1,
            }]
        },
        options: {
            plugins: { legend: { labels: { color: '#CBD5E1' } } },
            scales: {
                x: { ticks: { color: chartText }, grid: { color: chartGrid } },
                y: { ticks: { color: chartText }, grid: { color: chartGrid } },
            }
        }
    });
</script>
@endpush
