@extends('admin.layout')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard Overview')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
@endpush

@push('styles')
<style>
    .dash-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    .dash-card {
        padding: 16px;
        border-radius: 12px;
        background: var(--card-bg, #0f172a);
        border: 1px solid var(--border, rgba(148, 163, 184, 0.12));
    }
    .dash-card .k {
        color: var(--muted, #94a3b8);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .dash-card .v {
        margin-top: 6px;
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -0.02em;
    }
    
    .metric-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .badge-trend {
        padding: 4px 8px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }
    .badge-trend.up { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
    .badge-trend.down { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

    .charts-grid {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 16px;
        margin-top: 20px;
    }
    @media (max-width: 960px) { .charts-grid { grid-template-columns: 1fr; } }

    .chart-container {
        position: relative;
        height: 280px;
        width: 100%;
    }

    .quick-links {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
    }
    .quick-links a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 16px;
        border-radius: 8px;
        border: 1px solid var(--border, rgba(148, 163, 184, 0.2));
        color: var(--text, #f8fafc);
        font-weight: 600;
        font-size: 13px;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .quick-links a:hover {
        border-color: #f59e0b;
        background: rgba(245, 158, 11, 0.08);
    }
</style>
@endpush

@section('content')
<p class="page-hint" style="margin-bottom: 16px; color: var(--muted);">
    Overview of system submissions, active market density, and catalog coverage.
</p>

<!-- Stat Cards -->
<div class="dash-grid">
    <div class="card dash-card">
        <div class="k">Traders</div>
        <div class="v">{{ number_format($stats['trader_users']) }}</div>
    </div>
    <div class="card dash-card">
        <div class="k">Submissions Today</div>
        <div class="v">{{ number_format($stats['submissions_today']) }}</div>
    </div>
    <div class="card dash-card">
        <div class="k">Pending Approvals</div>
        <div class="v" style="color: {{ $stats['pending_approvals'] > 0 ? '#f59e0b' : 'inherit' }}">
            {{ number_format($stats['pending_approvals']) }}
        </div>
    </div>
    <div class="card dash-card">
        <div class="k">Active Markets</div>
        <div class="v">{{ number_format($stats['active_markets']) }}</div>
    </div>
    <div class="card dash-card">
        <div class="k">Products</div>
        <div class="v">{{ number_format($stats['products_count']) }}</div>
    </div>
</div>

<!-- Price Trend Highlight -->
<div class="card dash-card metric-hero" style="margin-bottom: 20px;">
    <div>
        <div class="k">Price Change (This Week vs. Prior Week)</div>
        <div class="v" style="margin-top: 4px;">{{ $stats['price_change_percent_this_week'] }}%</div>
    </div>
    <span class="badge-trend {{ $stats['price_change_percent_this_week'] >= 0 ? 'up' : 'down' }}">
        {{ $stats['price_change_percent_this_week'] >= 0 ? '↑' : '↓' }} {{ abs($stats['price_change_percent_this_week']) }}%
    </span>
</div>

<!-- Analytics Section -->
<div class="charts-grid">
    <div class="card">
        <div class="k" style="margin-bottom: 14px;">Submission Volume (30 Days)</div>
        <div class="chart-container">
            <canvas id="submissionsChart"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="k" style="margin-bottom: 14px;">Avg Price by Category (This Week)</div>
        <div class="chart-container">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

<!-- Table Section -->
<div class="card" style="margin-top: 20px;">
    <div class="k" style="margin-bottom: 14px;">Recent Pending Submissions</div>
    <div style="overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                    <th style="padding: 10px;">Product</th>
                    <th style="padding: 10px;">Market</th>
                    <th style="padding: 10px;">Price</th>
                    <th style="padding: 10px;">User</th>
                    <th style="padding: 10px;">Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($recentPending as $s)
                <tr style="border-bottom: 1px solid var(--border, rgba(148, 163, 184, 0.08));">
                    <td style="padding: 10px; font-weight: 600;">{{ $s->product?->name }}</td>
                    <td style="padding: 10px;">{{ $s->market?->name }}</td>
                    <td style="padding: 10px;">
                        ₦{{ number_format((float) $s->price, 2) }}
                        <div style="color: var(--muted); font-size: 11px;">
                            {{ rtrim(rtrim(number_format((float) ($s->quantity_value ?? 1), 3), '0'), '.') }} {{ $s->quantity_unit ?: $s->product?->unit }}
                        </div>
                    </td>
                    <td style="padding: 10px; color: var(--muted);">{{ $s->user?->email ?? '—' }}</td>
                    <td style="padding: 10px;"><span class="pill" style="padding: 4px 8px; border-radius: 4px; font-size: 11px; background: rgba(245, 158, 11, 0.15); color: #f59e0b;">{{ $s->status }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="padding: 16px; text-align: center; color: var(--muted);">No pending submissions found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Navigation Quick Links -->
<div class="quick-links">
    <a href="{{ route('admin.submissions.index') }}">Review All Submissions →</a>
    <a href="{{ route('admin.products.index') }}">Manage Products →</a>
    <a href="{{ route('admin.markets.index') }}">Manage Markets →</a>
</div>
@endsection

@push('scripts')
<script>
    const chartText = '#94A3B8';
    const chartGrid = 'rgba(148, 163, 184, 0.1)';

    new Chart(document.getElementById('submissionsChart'), {
        type: 'line',
        data: {
            labels: @json($submissionsChart['labels']),
            datasets: [{
                label: 'Submissions',
                data: @json($submissionsChart['values']),
                borderColor: '#22C55E',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: chartText }, grid: { color: chartGrid } },
                y: { ticks: { color: chartText }, grid: { color: chartGrid } }
            }
        }
    });

    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: @json($categoryChart['labels']),
            datasets: [{
                label: 'Avg ₦',
                data: @json($categoryChart['values']),
                backgroundColor: 'rgba(245, 158, 11, 0.65)',
                borderColor: '#F59E0B',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: chartText }, grid: { color: chartGrid } },
                y: { ticks: { color: chartText }, grid: { color: chartGrid } }
            }
        }
    });
</script>
@endpush
