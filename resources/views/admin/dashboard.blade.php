@extends('admin.layout')

@section('title', 'Admin Dashboard')
@section('page_title', 'Market Eye Executive Overview')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
@endpush

@push('styles')
<style>
    .dash-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    .dash-card {
        padding: 18px;
        border-radius: 14px;
        background: var(--card-bg, #0f172a);
        border: 1px solid var(--border, rgba(148, 163, 184, 0.12));
        position: relative;
        overflow: hidden;
    }
    .dash-card .k {
        color: var(--muted, #94a3b8);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .dash-card .v {
        margin-top: 8px;
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: var(--text, #f8fafc);
    }
    .dash-card .sub {
        margin-top: 6px;
        font-size: 12px;
        color: var(--muted, #94a3b8);
        font-weight: 500;
    }
    
    .metric-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95));
        border: 1px solid rgba(148, 163, 184, 0.18);
        margin-bottom: 20px;
    }
    .badge-trend {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 800;
    }
    .badge-trend.up { background: rgba(239, 68, 68, 0.18); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
    .badge-trend.down { background: rgba(34, 197, 94, 0.18); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); }

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

    .outlier-alert-banner {
        background: rgba(245, 158, 11, 0.12);
        border: 1px solid rgba(245, 158, 11, 0.4);
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .outlier-alert-banner strong {
        color: #f59e0b;
        font-size: 14px;
    }

    .quick-links {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 24px;
    }
    .quick-links a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 16px;
        border-radius: 10px;
        border: 1px solid var(--border, rgba(148, 163, 184, 0.2));
        color: var(--text, #f8fafc);
        font-weight: 600;
        font-size: 13px;
        text-decoration: none;
        background: rgba(30, 41, 59, 0.6);
        transition: all 0.2s ease;
    }
    .quick-links a:hover {
        border-color: #f59e0b;
        background: rgba(245, 158, 11, 0.12);
        color: #f59e0b;
    }

    .pill-geo {
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 800;
        background: rgba(34, 197, 94, 0.18);
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    .pill-outlier {
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 800;
        background: rgba(239, 68, 68, 0.18);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
</style>
@endpush

@section('content')
<p class="page-hint" style="margin-bottom: 16px; color: var(--muted);">
    Real-time telemetry on crowdsourced commodity submissions, data verification integrity, active markets, and automated push alerts.
</p>

@if ($stats['pending_outliers'] > 0)
<div class="outlier-alert-banner">
    <div>
        <strong>⚠️ {{ $stats['pending_outliers'] }} Statistical Outlier(s) Flagged</strong>
        <p style="margin: 2px 0 0; font-size: 12px; color: var(--muted);">
            IQR Anomaly filter quarantined abnormal submissions (prices deviating from 30-day baseline) for manual review.
        </p>
    </div>
    <a href="{{ route('admin.submissions.index') }}" class="btn btn-sm" style="background: #f59e0b; color: #000; font-weight: 800; padding: 6px 12px; border-radius: 8px; text-decoration: none;">
        Review Outliers →
    </a>
</div>
@endif

<!-- Primary Stat KPI Cards -->
<div class="dash-grid">
    <div class="card dash-card">
        <div class="k">
            <span>Submissions Today</span>
            <span style="color: #22c55e;">● Live</span>
        </div>
        <div class="v">{{ number_format($stats['submissions_today']) }}</div>
        <div class="sub">{{ number_format($stats['submissions_30d']) }} reports in last 30d ({{ number_format($stats['total_submissions']) }} total)</div>
    </div>

    <div class="card dash-card">
        <div class="k">
            <span>Pending Approvals</span>
            @if ($stats['pending_outliers'] > 0)
                <span style="color: #ef4444;">{{ $stats['pending_outliers'] }} Outliers</span>
            @endif
        </div>
        <div class="v" style="color: {{ $stats['pending_approvals'] > 0 ? '#f59e0b' : 'inherit' }}">
            {{ number_format($stats['pending_approvals']) }}
        </div>
        <div class="sub">{{ $stats['approval_rate'] }}% historical approval rate</div>
    </div>

    <div class="card dash-card">
        <div class="k">
            <span>Registered Traders</span>
            <span style="color: #3b82f6;">👥 Users</span>
        </div>
        <div class="v">{{ number_format($stats['trader_users']) }}</div>
        <div class="sub">{{ number_format($stats['active_traders_30d']) }} active submitters this month</div>
    </div>

    <div class="card dash-card">
        <div class="k">
            <span>GPS Geoverified</span>
            <span style="color: #22c55e;">📍 On-Site</span>
        </div>
        <div class="v">{{ number_format($stats['geoverified_count']) }}</div>
        <div class="sub">{{ $stats['geoverified_rate'] }}% submitted within 750m of market</div>
    </div>

    <div class="card dash-card">
        <div class="k">
            <span>Active Price Alerts</span>
            <span style="color: #f59e0b;">🔔 Rules</span>
        </div>
        <div class="v">{{ number_format($stats['active_price_alerts']) }}</div>
        <div class="sub">{{ number_format($stats['push_device_tokens']) }} push-enabled devices (FCM/Expo)</div>
    </div>

    <div class="card dash-card">
        <div class="k">
            <span>Airtime Liability</span>
            <span style="color: #10b981;">₦ Rewards</span>
        </div>
        <div class="v">₦{{ number_format($stats['total_liability'], 0) }}</div>
        <div class="sub">{{ $stats['pending_claims_count'] }} pending claims (₦{{ number_format($stats['pending_claims_amount'], 0) }})</div>
    </div>
</div>

<!-- Secondary Metrics Bar -->
<div class="dash-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
    <div class="card dash-card" style="padding: 14px;">
        <div class="k">Monitored Markets</div>
        <div class="v" style="font-size: 22px;">{{ $stats['active_markets'] }}</div>
        <div class="sub">Top: {{ $stats['most_active_market'] }}</div>
    </div>

    <div class="card dash-card" style="padding: 14px;">
        <div class="k">Active Commodities</div>
        <div class="v" style="font-size: 22px;">{{ $stats['products_count'] }}</div>
        <div class="sub">Across {{ $stats['categories_count'] }} food categories</div>
    </div>

    <div class="card dash-card" style="padding: 14px;">
        <div class="k">Price Snapshots</div>
        <div class="v" style="font-size: 22px;">{{ number_format($stats['total_snapshots']) }}</div>
        <div class="sub">Aggregated daily benchmarks</div>
    </div>

    <div class="card dash-card" style="padding: 14px;">
        <div class="k">Developer API Keys</div>
        <div class="v" style="font-size: 22px;">{{ $stats['active_api_keys'] }}</div>
        <div class="sub">Active public consumers</div>
    </div>
</div>

<!-- Price Trend Highlight -->
<div class="metric-hero">
    <div>
        <div style="color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
            Abuja Food Inflation Index (7-Day Shift)
        </div>
        <div style="font-size: 24px; font-weight: 800; margin-top: 4px; color: #f8fafc;">
            {{ $stats['price_change_percent_this_week'] > 0 ? '+' : '' }}{{ $stats['price_change_percent_this_week'] }}%
            <span style="font-size: 13px; font-weight: 500; color: var(--muted); margin-left: 8px;">
                Average commodity price shift vs. prior 7 days
            </span>
        </div>
    </div>
    <span class="badge-trend {{ $stats['price_change_percent_this_week'] >= 0 ? 'up' : 'down' }}">
        {{ $stats['price_change_percent_this_week'] >= 0 ? '↑ Inflation + ' : '↓ Deflation - ' }} {{ abs($stats['price_change_percent_this_week']) }}%
    </span>
</div>

<!-- Visual Analytics Charts -->
<div class="charts-grid">
    <div class="card">
        <div class="k" style="margin-bottom: 14px; font-size: 13px; font-weight: 800; color: #f8fafc;">
            📊 Submission Volume Trend (Last 30 Days)
        </div>
        <div class="chart-container">
            <canvas id="submissionsChart"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="k" style="margin-bottom: 14px; font-size: 13px; font-weight: 800; color: #f8fafc;">
            🏷️ Benchmark Price by Category (₦)
        </div>
        <div class="chart-container">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

<!-- Pending Submissions Queue -->
<div class="card" style="margin-top: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <div class="k" style="font-size: 14px; font-weight: 800; color: #f8fafc;">
            ⏳ Moderation Queue (Recent Pending Submissions)
        </div>
        <a href="{{ route('admin.submissions.index') }}" style="color: #f59e0b; font-size: 12px; font-weight: 700; text-decoration: none;">
            View All Pending ({{ $stats['pending_approvals'] }}) →
        </a>
    </div>

    <div style="overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                    <th style="padding: 10px;">Commodity</th>
                    <th style="padding: 10px;">Market</th>
                    <th style="padding: 10px;">Submitted Price</th>
                    <th style="padding: 10px;">Reporter</th>
                    <th style="padding: 10px;">Integrity Checks</th>
                    <th style="padding: 10px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($recentPending as $s)
                <tr style="border-bottom: 1px solid var(--border, rgba(148, 163, 184, 0.08));">
                    <td style="padding: 10px; font-weight: 700; color: #f8fafc;">
                        {{ $s->product?->name }}
                    </td>
                    <td style="padding: 10px; color: #cbd5e1;">{{ $s->market?->name }}</td>
                    <td style="padding: 10px;">
                        <span style="font-weight: 800; color: #22c55e;">₦{{ number_format((float) $s->price, 2) }}</span>
                        <div style="color: var(--muted); font-size: 11px;">
                            {{ rtrim(rtrim(number_format((float) ($s->quantity_value ?? 1), 3), '0'), '.') }} {{ $s->quantity_unit ?: $s->product?->unit }}
                            (₦{{ number_format((float) $s->price_per_unit, 2) }}/unit)
                        </div>
                    </td>
                    <td style="padding: 10px; color: var(--muted); font-size: 12px;">
                        {{ $s->user?->name ?? 'User' }}
                        <div style="font-size: 11px; color: #64748b;">{{ $s->user?->email }}</div>
                    </td>
                    <td style="padding: 10px;">
                        @if ($s->is_geoverified)
                            <span class="pill-geo">📍 On-Site</span>
                        @endif

                        @if ($s->is_outlier)
                            <span class="pill-outlier" title="{{ $s->outlier_reason }}">⚠️ Outlier</span>
                            <div style="font-size: 10px; color: #ef4444; max-width: 180px; margin-top: 2px;" title="{{ $s->outlier_reason }}">
                                {{ Str::limit($s->outlier_reason, 35) }}
                            </div>
                        @endif

                        @if (! $s->is_geoverified && ! $s->is_outlier)
                            <span style="font-size: 11px; color: var(--muted);">Standard</span>
                        @endif
                    </td>
                    <td style="padding: 10px; text-align: right;">
                        <form action="{{ route('admin.submissions.approve', $s->id) }}" method="POST" style="display: inline-block;">
                            @csrf
                            <button type="submit" class="btn btn-sm" style="background: rgba(34, 197, 94, 0.2); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.4); padding: 4px 8px; border-radius: 6px; font-weight: 700; cursor: pointer;">
                                Approve
                            </button>
                        </form>
                        <form action="{{ route('admin.submissions.reject', $s->id) }}" method="POST" style="display: inline-block; margin-left: 4px;">
                            @csrf
                            <button type="submit" class="btn btn-sm" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 4px 8px; border-radius: 6px; font-weight: 700; cursor: pointer;">
                                Reject
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="padding: 24px; text-align: center; color: var(--muted);">
                        🎉 All price submissions are currently reviewed and approved!
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Navigation Quick Links -->
<div class="quick-links">
    <a href="{{ route('admin.submissions.index') }}">📋 Submissions & Outliers ({{ $stats['pending_approvals'] }})</a>
    <a href="{{ route('admin.claims.index') }}">💳 Airtime Claims ({{ $stats['pending_claims_count'] }})</a>
    <a href="{{ route('admin.markets.index') }}">🏪 Markets ({{ $stats['active_markets'] }})</a>
    <a href="{{ route('admin.products.index') }}">🌾 Products ({{ $stats['products_count'] }})</a>
    <a href="{{ route('admin.analytics.index') }}">📈 Inflation Analytics</a>
    <a href="{{ route('admin.api-keys.index') }}">🔑 Developer API Keys ({{ $stats['active_api_keys'] }})</a>
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
                label: 'Price Reports',
                data: @json($submissionsChart['values']),
                borderColor: '#22C55E',
                backgroundColor: 'rgba(34, 197, 94, 0.12)',
                fill: true,
                tension: 0.35,
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#22C55E'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.parsed.y} price submission(s)`
                    }
                }
            },
            scales: {
                x: { ticks: { color: chartText, maxTicksLimit: 10 }, grid: { color: chartGrid } },
                y: { ticks: { color: chartText }, grid: { color: chartGrid }, beginAtZero: true }
            }
        }
    });

    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: @json($categoryChart['labels']),
            datasets: [{
                label: 'Avg Price (₦)',
                data: @json($categoryChart['values']),
                backgroundColor: 'rgba(245, 158, 11, 0.75)',
                borderColor: '#F59E0B',
                borderWidth: 1.5,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `₦${Number(ctx.parsed.y).toLocaleString()}`
                    }
                }
            },
            scales: {
                x: { ticks: { color: chartText }, grid: { display: false } },
                y: {
                    ticks: {
                        color: chartText,
                        callback: (v) => '₦' + Number(v).toLocaleString()
                    },
                    grid: { color: chartGrid },
                    beginAtZero: true
                }
            }
        }
    });
</script>
@endpush
