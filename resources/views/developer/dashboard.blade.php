@extends('developer.layout')

@section('title', 'Dashboard')

@section('content')
<h1>Welcome, {{ $developer->name }}</h1>
<p class="muted">
    Max {{ $maxKeys }} active keys · default {{ $defaultLimit }} requests/day · self-serve max {{ $maxSelfLimit }}/day.
    Need more? Contact the Market Eye team — admins can raise limits after review.
</p>

<div class="card">
    <h2 style="margin-top:0;">Create API key</h2>
    <form method="post" action="{{ route('developer.keys.store') }}">
        @csrf
        <label>Key name</label>
        <input name="name" required placeholder="Production app">
        <label>Daily limit (max {{ $maxSelfLimit }})</label>
        <input type="number" name="daily_limit" min="10" max="{{ $maxSelfLimit }}" value="{{ $defaultLimit }}">
        <button class="btn" type="submit">Generate key</button>
    </form>
</div>

<div class="card">
    <h2 style="margin-top:0;">Your keys</h2>
    <div style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Prefix</th>
                <th>Daily limit</th>
                <th>Active</th>
                <th>Last used</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($keys as $key)
                <tr>
                    <td>{{ $key->name }}</td>
                    <td><code>{{ $key->key_prefix }}…</code></td>
                    <td>{{ number_format($key->daily_limit) }}</td>
                    <td>{{ $key->is_active ? 'yes' : 'no' }}</td>
                    <td>{{ $key->last_used_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>
                        @if ($key->is_active)
                            <form method="post" action="{{ route('developer.keys.revoke', $key->id) }}">
                                @csrf
                                <button class="btn btn-ghost" type="submit">Revoke</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="color:var(--muted);">No keys yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2 style="margin-top:0;">Quick start</h2>
    <pre style="background:#0B1220;border:1px solid var(--border);border-radius:12px;padding:14px;overflow:auto;color:#D1FAE5;"><code>curl "{{ url('/api/v1/public/markets') }}" \
  -H "X-API-Key: me_your_key_here"</code></pre>
    <p class="muted"><a href="{{ route('developers') }}">Full API documentation →</a></p>
</div>
@endsection
