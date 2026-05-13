@extends('admin.layout')

@section('title', 'Activity')

@section('page_title', 'Recent admin activity')

@section('content')
<p class="page-hint">A log of actions taken in this admin panel (approvals, edits, etc.).</p>

<div class="card">
    <div style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th>When</th>
                <th>Admin</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($activity as $a)
                <tr>
                    <td>{{ $a->created_at?->toDateTimeString() }}</td>
                    <td>{{ $a->admin?->email }}</td>
                    <td>{{ $a->action }}</td>
                </tr>
            @endforeach
            @if ($activity->isEmpty())
                <tr><td colspan="3" style="color:var(--muted);">No activity yet.</td></tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
