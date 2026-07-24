@extends('admin.layout')

@section('title', 'Airtime claims')

@section('page_title', 'Airtime claims')

@section('content')
<p class="page-hint">
    Users earn ₦1 per verified price submission. When their wallet reaches ₦200 they can claim airtime.
    Send airtime to the phone number below, then mark the claim as paid. Rejecting refunds the wallet.
    {{ $pendingCount }} pending.
</p>

<div class="card">
    <div style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th>User</th>
                <th>Phone</th>
                <th>Amount</th>
                <th>Claimed</th>
                <th>Status</th>
                <th>Paid by</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($claims as $claim)
                <tr>
                    <td>
                        <div style="font-weight:800;">{{ $claim->user?->name }}</div>
                        <div style="color:var(--muted);font-size:12px;">{{ $claim->user?->email }}</div>
                    </td>
                    <td style="font-weight:800;">{{ $claim->phone }}</td>
                    <td>₦{{ number_format((int) $claim->amount) }}</td>
                    <td>{{ $claim->claimed_at?->format('Y-m-d H:i') }}</td>
                    <td><span class="pill">{{ $claim->status }}</span></td>
                    <td>{{ $claim->payer?->name ?? '—' }}</td>
                    <td>
                        @if ($claim->status === 'pending')
                            <form method="post" action="{{ route('admin.claims.paid', $claim->id) }}" style="display:inline-block;margin-bottom:8px;">
                                @csrf
                                <input type="hidden" name="admin_note" value="Airtime sent.">
                                <button class="btn btn-primary" type="submit">Mark paid</button>
                            </form>
                            <form method="post" action="{{ route('admin.claims.reject', $claim->id) }}" style="display:inline-block;">
                                @csrf
                                <input type="hidden" name="admin_note" value="Could not send airtime.">
                                <button class="btn" type="submit">Reject / refund</button>
                            </form>
                        @else
                            <span style="color:var(--muted);font-size:12px;">{{ $claim->admin_note ?? '—' }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            @if ($claims->isEmpty())
                <tr><td colspan="7" style="color:var(--muted);">No airtime claims yet.</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
