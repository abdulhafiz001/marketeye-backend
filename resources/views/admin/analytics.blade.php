@extends('admin.layout')

@section('title', 'Analytics')

@section('page_title', 'Analytics')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@push('styles')
<style>
    .analytics-grid { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
    .analytics-split { display:grid; grid-template-columns: 1.2fr 1fr; gap: 12px; margin-bottom: 16px; }
    @media (max-width: 1100px) {
        .analytics-grid { grid-template-columns: repeat(2, 1fr); }
        .analytics-split { grid-template-columns: 1fr; }
    }
    .metric-label { color: var(--muted); font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; }
    .metric-value { margin-top: 8px; font-size: 24px; font-weight: 900; }
    .heatmap {
        display: grid;
        grid-template-columns: repeat(15, minmax(0, 1fr));
        gap: 4px;
    }
    .heat-cell {
        aspect-ratio: 1;
        border-radius: 4px;
        border: 1px solid var(--border);
        background: #0B1220;
    }
    .heat-0 { background: #0B1220; }
    .heat-1 { background: rgba(34,197,94,.18); }
    .heat-2 { background: rgba(34,197,94,.35); }
    .heat-3 { background: rgba(34,197,94,.55); }
    .heat-4 { background: rgba(34,197,94,.8); }
    .section-title { font-weight: 900; margin: 0 0 12px; font-size: 15px; }
</style>
@endpush

@section('content')
<p class="page-hint">
    Operational story for Market Eye: inflation by market/product, submission volume, contributor quality, rejection reasons, and airtime wallet liability.
</p>

@php
    $liability = $wallet_liability;
    $heatmap = $submission_heatmap;
    $maxHeat = max(1, collect($heatmap)->max('count'));
@endphp

<div class="analytics-grid">
    <div class="card">
        <div class="metric-label">Pending airtime claims</div>
        <div class="metric-value">{{ number_format($liability['pending_claims_count']) }}</div>
        <div style="color:var(--muted);font-weight:700;margin-top:6px;">₦{{ number_format($liability['pending_claims_amount']) }} held</div>
    </div>
    <div class="card">
        <div class="metric-label">Unclaimed wallet balances</div>
        <div class="metric-value">₦{{ number_format($liability['unclaimed_wallet_balance']) }}</div>
        <div style="color:var(--muted);font-weight:700;margin-top:6px;">Sitting in user wallets</div>
    </div>
    <div class="card">
        <div class="metric-label">Total outstanding ₦</div>
        <div class="metric-value">₦{{ number_format($liability['total_outstanding_naira']) }}</div>
        <div style="color:var(--muted);font-weight:700;margin-top:6px;">Claims + wallets</div>
    </div>
    <div class="card">
        <div class="metric-label">Min claim threshold</div>
        <div class="metric-value">₦{{ number_format($liability['min_claim_amount']) }}</div>
        <div style="color:var(--muted);font-weight:700;margin-top:6px;">Airtime payout floor</div>
    </div>
</div>

<div class="analytics-split">
    <div class="card">
        <div class="section-title">Submission volume (90 days)</div>
        <canvas id="volumeChart" height="120"></canvas>
        <div style="margin-top:16px;">
            <div class="section-title" style="font-size:13px;">Heatmap (darker = more submissions)</div>
            <div class="heatmap" title="Daily submission counts">
                @foreach ($heatmap as $day)
                    @php
                        $ratio = $day['count'] / $maxHeat;
                        $level = $day['count'] === 0 ? 0 : ($ratio < 0.25 ? 1 : ($ratio < 0.5 ? 2 : ($ratio < 0.75 ? 3 : 4)));
                    @endphp
                    <div class="heat-cell heat-{{ $level }}" title="{{ $day['date'] }}: {{ $day['count'] }} submissions"></div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="card">
        <div class="section-title">Rejection reasons (30 days)</div>
        <canvas id="rejectChart" height="160"></canvas>
        <div style="overflow:auto;margin-top:14px;max-height:220px;">
            <table>
                <thead><tr><th>Reason</th><th>Count</th></tr></thead>
                <tbody>
                @forelse ($rejection_reasons as $row)
                    <tr>
                        <td>{{ $row['reason'] }}</td>
                        <td>{{ $row['count'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">No rejections in the last 30 days.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="analytics-split">
    <div class="card">
        <div class="section-title">Price inflation — 30 days</div>
        <div style="overflow:auto;max-height:360px;">
            <table>
                <thead>
                <tr>
                    <th>Product</th>
                    <th>Market</th>
                    <th>Now</th>
                    <th>Before</th>
                    <th>Change</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($inflation['30d'] as $row)
                    <tr>
                        <td>{{ $row['product_name'] }}</td>
                        <td>{{ $row['market_name'] }}</td>
                        <td>₦{{ number_format($row['current_avg'], 0) }}</td>
                        <td>₦{{ number_format($row['previous_avg'], 0) }}</td>
                        <td style="font-weight:900;color:{{ $row['change_percent'] >= 0 ? '#FCA5A5' : '#86EFAC' }};">
                            {{ $row['change_percent'] > 0 ? '+' : '' }}{{ $row['change_percent'] }}%
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Not enough snapshot history yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="section-title">Price inflation — 90 days</div>
        <div style="overflow:auto;max-height:360px;">
            <table>
                <thead>
                <tr>
                    <th>Product</th>
                    <th>Market</th>
                    <th>Now</th>
                    <th>Before</th>
                    <th>Change</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($inflation['90d'] as $row)
                    <tr>
                        <td>{{ $row['product_name'] }}</td>
                        <td>{{ $row['market_name'] }}</td>
                        <td>₦{{ number_format($row['current_avg'], 0) }}</td>
                        <td>₦{{ number_format($row['previous_avg'], 0) }}</td>
                        <td style="font-weight:900;color:{{ $row['change_percent'] >= 0 ? '#FCA5A5' : '#86EFAC' }};">
                            {{ $row['change_percent'] > 0 ? '+' : '' }}{{ $row['change_percent'] }}%
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Not enough snapshot history yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="section-title">Top contributors</div>
    <div style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Approved</th>
                <th>Total submissions</th>
                <th>Points</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($top_contributors as $user)
                <tr>
                    <td>{{ $user['name'] }}</td>
                    <td>{{ $user['approved_count'] }}</td>
                    <td>{{ $user['submission_count'] }}</td>
                    <td>{{ number_format($user['points']) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No contributors yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const heatmap = @json($heatmap);
    const rejects = @json($rejection_reasons);

    const volumeLabels = heatmap.map((d) => d.date.slice(5));
    const volumeData = heatmap.map((d) => d.count);

    new Chart(document.getElementById('volumeChart'), {
        type: 'bar',
        data: {
            labels: volumeLabels,
            datasets: [{
                label: 'Submissions',
                data: volumeData,
                backgroundColor: 'rgba(34,197,94,0.45)',
                borderColor: 'rgba(34,197,94,0.9)',
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { maxTicksLimit: 12, color: '#94A3B8' }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { color: '#94A3B8' }, grid: { color: '#1F2937' } },
            },
        },
    });

    new Chart(document.getElementById('rejectChart'), {
        type: 'doughnut',
        data: {
            labels: rejects.length ? rejects.map((r) => r.reason) : ['No rejections'],
            datasets: [{
                data: rejects.length ? rejects.map((r) => r.count) : [1],
                backgroundColor: ['#F87171','#FBBF24','#60A5FA','#34D399','#A78BFA','#FB7185','#FCD34D'],
            }],
        },
        options: {
            plugins: {
                legend: { position: 'bottom', labels: { color: '#94A3B8', boxWidth: 12 } },
            },
        },
    });
})();
</script>
@endpush
