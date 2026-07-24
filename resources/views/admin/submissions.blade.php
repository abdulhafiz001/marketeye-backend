@extends('admin.layout')

@section('title', 'Submissions')

@section('page_title', 'Manage submissions')

@section('content')
<p class="page-hint">Approve or reject price submissions. Approved entries update market snapshots when rules allow.</p>

<div class="card">
    <div style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th>Product</th>
                <th>Market</th>
                <th>Price</th>
                <th>User</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($submissions as $s)
                <tr>
                    <td>{{ $s->product?->name }}</td>
                    <td>{{ $s->market?->name }}</td>
                    <td>
                        ₦{{ number_format((float) $s->price, 2) }}
                        <div style="color:var(--muted);font-size:11px;">
                            {{ rtrim(rtrim(number_format((float) ($s->quantity_value ?? 1), 3), '0'), '.') }} {{ $s->quantity_unit ?: $s->product?->unit }}
                        </div>
                        <div style="color:var(--muted);font-size:11px;">
                            Unit: ₦{{ number_format((float) ($s->price_per_unit ?: $s->price), 2) }}
                        </div>
                    </td>
                    <td>{{ $s->user?->email ?? '—' }}</td>
                    <td><span class="pill">{{ $s->status }}</span></td>
                    <td style="white-space:nowrap;">
                        @if ($s->status === 'pending')
                            <form method="post" action="{{ route('admin.submissions.approve', $s->id) }}" style="display:inline-flex;gap:6px;align-items:center;">
                                @csrf
                                <select name="confidence_level" style="padding:6px 8px;border-radius:8px;border:1px solid #E5E7EB;font-size:12px;">
                                    <option value="medium" selected>Confident</option>
                                    <option value="high">High confidence</option>
                                    <option value="low">Low confidence</option>
                                </select>
                                <button class="btn btn-primary" type="submit">Approve</button>
                            </form>
                            <form method="post" action="{{ route('admin.submissions.reject', $s->id) }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="reason" value="Rejected from admin panel">
                                <button class="btn" type="submit">Reject</button>
                            </form>
                        @else
                            <span style="color:var(--muted);font-size:12px;">—</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            @if ($submissions->isEmpty())
                <tr><td colspan="6" style="color:var(--muted);">No submissions yet.</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
