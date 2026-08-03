@extends('admin.layout')

@section('title', 'Prices')
@section('page_title', 'Manage Prices')

@push('styles')
<style>
    .card-head { 
        display: flex; 
        align-items: baseline; 
        justify-content: space-between; 
        gap: 10px; 
        margin-bottom: 16px; 
    }
    .card-head .k { color: var(--muted); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
    .card-head .sub { color: var(--muted); font-size: 12px; }

    .price-form { 
        display: grid;
        grid-template-columns: minmax(200px, 1.2fr) minmax(280px, 1.8fr) minmax(130px, 0.8fr) minmax(140px, 0.8fr) auto; 
        gap: 14px;
        align-items: end; 
    }

    .market-picker-head { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .market-filter {
        flex: 1;
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid var(--border, rgba(148, 163, 184, 0.2));
        background: rgba(15, 23, 42, 0.6);
        color: var(--text);
        font-size: 13px;
    }
    .market-select-all { display: flex; align-items: center; gap: 6px; color: var(--muted); font-size: 11px; font-weight: 700; cursor: pointer; }
    .market-select-all input { width: 14px; height: 14px; accent-color: #22C55E; }
    
    .market-picker {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
        max-height: 200px;
        overflow-y: auto;
        padding: 6px;
        border: 1px solid var(--border, rgba(148, 163, 184, 0.2));
        border-radius: 10px;
        background: rgba(15, 23, 42, 0.4);
    }
    .market-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border: 1px solid rgba(148,163,184,.12);
        border-radius: 6px;
        background: rgba(15, 23, 42, 0.8);
        color: var(--text);
        cursor: pointer;
        font-size: 12.5px;
        font-weight: 600;
        transition: all .15s ease;
    }
    .market-option:hover { border-color: rgba(34,197,94,.4); background: rgba(34,197,94,.08); }
    .market-option.is-hidden { display: none !important; }
    .market-option input { width: 16px; height: 16px; accent-color: #22C55E; }
    .market-option span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .inline-price-form {
        display: flex;
        gap: 6px;
        align-items: center;
    }
    .inline-price-form input { width: 90px; }

    tbody tr:hover { background: rgba(148, 163, 184, 0.04); }

    /* Custom Price Bar Component */
    .range-cell { display: flex; flex-direction: column; gap: 4px; min-width: 140px; }
    .range-cell .avg { font-weight: 800; font-size: 13px; }
    .range-cell .lowhigh { color: var(--muted); font-size: 10px; display: flex; justify-content: space-between; }
    .range-track { position: relative; height: 5px; border-radius: 999px; background: rgba(148, 163, 184, 0.2); }
    .range-marker { position: absolute; top: 50%; width: 9px; height: 9px; border-radius: 50%; background: #F59E0B; border: 2px solid #0f172a; transform: translate(-50%, -50%); }

    .source-pill { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    .source-pill.is-admin { background: rgba(59,130,246,.15); color: #60A5FA; }
    .source-pill.is-trader { background: rgba(34,197,94,.15); color: #4ADE80; }

    @media (max-width: 1100px) {
        .price-form { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 760px) {
        .price-form { grid-template-columns: 1fr; }
        .market-picker { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<p class="page-hint" style="margin-bottom: 16px; color: var(--muted);">
    Set or update prices across markets to refresh live snapshot metrics.
</p>

<!-- Form Card -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-head">
        <span class="k">Add or Update Price Snapshots</span>
        <span class="sub">Applies to all selected markets simultaneously</span>
    </div>
    <form method="post" action="{{ route('admin.prices.store') }}" class="price-form">
        @csrf
        <div>
            <label style="display:block; margin-bottom: 6px; font-size: 12px; font-weight: 700;">Product</label>
            <select name="product_id" required style="width:100%;">
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->unit }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block; margin-bottom: 6px; font-size: 12px; font-weight: 700;">Target Markets</label>
            <div class="market-picker-head">
                <input type="text" class="market-filter" id="marketFilter" placeholder="Search market...">
                <label class="market-select-all">
                    <input type="checkbox" id="marketSelectAll"> Select Visible
                </label>
            </div>
            <div class="market-picker" id="marketPicker">
                @foreach ($markets as $market)
                    <label class="market-option">
                        <input type="checkbox" name="market_ids[]" value="{{ $market->id }}">
                        <span>{{ $market->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div>
            <label style="display:block; margin-bottom: 6px; font-size: 12px; font-weight: 700;">Price (₦)</label>
            <input name="price" type="number" min="1" step="0.01" placeholder="3500" required style="width:100%;">
        </div>
        <div>
            <label style="display:block; margin-bottom: 6px; font-size: 12px; font-weight: 700;">Effective Date</label>
            <input name="effective_date" type="date" value="{{ now()->toDateString() }}" required style="width:100%;">
        </div>
        <div>
            <button class="btn btn-primary" type="submit" style="width: 100%;">Save Snapshots</button>
        </div>
    </form>
</div>

<!-- Snapshots Data Card -->
<div class="card">
    <div class="card-head">
        <span class="k">Current Snapshots</span>
        <span class="sub">{{ $snapshots->count() }} records</span>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border, rgba(148, 163, 184, 0.12)); color: var(--muted); font-size: 12px;">
                    <th style="padding: 10px;">Product</th>
                    <th style="padding: 10px;">Market</th>
                    <th style="padding: 10px;">Price Range</th>
                    <th style="padding: 10px;">Submissions</th>
                    <th style="padding: 10px;">Source</th>
                    <th style="padding: 10px;">Date</th>
                    <th style="padding: 10px; text-align: right;">Quick Update</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($snapshots as $snapshot)
                @php
                    $min = (float) $snapshot->min_price;
                    $max = (float) $snapshot->max_price;
                    $avg = (float) $snapshot->avg_price;
                    $rangePct = $max > $min ? min(max((($avg - $min) / ($max - $min)) * 100, 0), 100) : 50;
                    $isAdminSource = str_contains(strtolower($snapshot->snapshot_source ?? ''), 'admin');
                @endphp
                <tr style="border-bottom: 1px solid var(--border, rgba(148, 163, 184, 0.08));">
                    <td style="padding: 10px; font-weight: 600;">
                        {{ $snapshot->product?->name }}
                        <div style="color:var(--muted); font-size: 11px; font-weight: normal;">{{ $snapshot->product?->unit }}</div>
                    </td>
                    <td style="padding: 10px;">{{ $snapshot->market?->name }}</td>
                    <td style="padding: 10px;">
                        <div class="range-cell">
                            <span class="avg">₦{{ number_format($avg, 2) }}</span>
                            <div class="range-track"><span class="range-marker" style="left: {{ $rangePct }}%;"></span></div>
                            <div class="lowhigh"><span>₦{{ number_format($min, 0) }}</span><span>₦{{ number_format($max, 0) }}</span></div>
                        </div>
                    </td>
                    <td style="padding: 10px;">{{ number_format($snapshot->submission_count) }}</td>
                    <td style="padding: 10px;">
                        <span class="source-pill {{ $isAdminSource ? 'is-admin' : 'is-trader' }}">
                            {{ str_replace('_', ' ', $snapshot->snapshot_source) }}
                        </span>
                    </td>
                    <td style="padding: 10px; color: var(--muted); font-size: 12px;">{{ $snapshot->snapshot_date?->toDateString() }}</td>
                    <td style="padding: 10px; text-align: right;">
                        <form method="post" action="{{ route('admin.prices.store') }}" class="inline-price-form" style="justify-content: flex-end;">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $snapshot->product_id }}">
                            <input type="hidden" name="market_id" value="{{ $snapshot->market_id }}">
                            <input type="hidden" name="effective_date" value="{{ now()->toDateString() }}">
                            <input name="price" type="number" min="1" step="0.01" value="{{ (float) $snapshot->avg_price }}" required>
                            <button class="btn" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="padding: 16px; text-align: center; color: var(--muted);">No price snapshots recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const filterInput = document.getElementById('marketFilter');
        const selectAll = document.getElementById('marketSelectAll');
        const options = Array.from(document.querySelectorAll('#marketPicker .market-option'));

        filterInput?.addEventListener('input', () => {
            const query = filterInput.value.trim().toLowerCase();
            options.forEach((opt) => {
                const label = opt.querySelector('span').textContent.toLowerCase();
                opt.classList.toggle('is-hidden', query.length > 0 && !label.includes(query));
            });
            if (selectAll) selectAll.checked = false;
        });

        selectAll?.addEventListener('change', () => {
            options
                .filter((opt) => !opt.classList.contains('is-hidden'))
                .forEach((opt) => { 
                    const cb = opt.querySelector('input');
                    if (cb) cb.checked = selectAll.checked; 
                });
        });
    })();
</script>
@endpush