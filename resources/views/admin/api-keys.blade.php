@extends('admin.layout')

@section('title', 'API keys')

@section('page_title', 'API keys (oversight)')

@section('content')
<p class="page-hint">
    Developers create keys in the <strong>developer portal</strong> ({{ url('/developer') }}).
    Here you oversee all keys: raise/lower daily limits (up to 50,000) and revoke abuse.
    Default self-serve is 200/day (max 500). Keep limits tight.
</p>

<div class="card">
    <div style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th>Developer</th>
                <th>Name</th>
                <th>Prefix</th>
                <th>Daily limit</th>
                <th>Active</th>
                <th>Last used</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($keys as $key)
                <tr>
                    <td>
                        <div style="font-weight:800;">{{ $key->developer?->name ?? '—' }}</div>
                        <div style="color:var(--muted);font-size:12px;">{{ $key->developer?->email }}</div>
                    </td>
                    <td>{{ $key->name }}</td>
                    <td><code>{{ $key->key_prefix }}…</code></td>
                    <td>
                        <form method="post" action="{{ route('admin.api-keys.update', $key->id) }}" class="form-grid" style="grid-template-columns:1fr auto;margin:0;align-items:center;">
                            @csrf
                            <input type="number" name="daily_limit" min="10" max="50000" value="{{ $key->daily_limit }}" style="margin:0;">
                            <input type="hidden" name="is_active" value="{{ $key->is_active ? 1 : 0 }}">
                            <button class="btn" type="submit">Save</button>
                        </form>
                    </td>
                    <td>{{ $key->is_active ? 'yes' : 'revoked' }}</td>
                    <td>{{ $key->last_used_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>
                        @if ($key->is_active)
                            <form method="post" action="{{ route('admin.api-keys.revoke', $key->id) }}">
                                @csrf
                                <button class="btn" type="submit">Revoke</button>
                            </form>
                        @else
                            <span style="color:var(--muted);">—</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            @if ($keys->isEmpty())
                <tr><td colspan="7" style="color:var(--muted);">No developer API keys yet.</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
