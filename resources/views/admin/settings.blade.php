@extends('admin.layout')

@section('title', 'Settings')

@section('page_title', 'Settings')

@push('styles')
<style>
    .tabs { display:flex; gap:8px; margin-bottom:18px; flex-wrap:wrap; }
    .tab {
        display:inline-block; padding:10px 14px; border-radius:12px; border:1px solid var(--border);
        color: var(--muted); text-decoration:none; font-weight:800; font-size:13px;
    }
    .tab:hover { color: var(--text); background: var(--sidebar-hover); }
    .tab.active {
        color: var(--text);
        background: rgba(34,197,94,.12);
        border-color: rgba(34,197,94,.35);
    }
    .settings-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 900px) { .settings-grid { grid-template-columns: 1fr; } }
    .field { margin-bottom: 12px; }
    .pill-main { color:#86EFAC; border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.12); }
    .pill-restricted { color:#FCA5A5; border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.12); }
    .admin-card { margin-bottom: 14px; }
    .admin-card h3 { margin: 0 0 10px; font-size: 15px; }
    .check-row { display:flex; align-items:center; gap:10px; margin: 12px 0; color: var(--muted); font-weight:700; font-size:13px; }
    .check-row input { width:auto; }
    .locked-note { color:#FBBF24; font-size:12px; font-weight:700; margin-top:8px; }
</style>
@endpush

@section('content')
<p class="page-hint">
    Manage your account
    @if ($actor->canManageAdmins())
        and the admin team. The <strong>main admin</strong> (first account) can never be restricted.
    @endif
</p>

<div class="tabs">
    <a class="tab {{ $tab === 'account' ? 'active' : '' }}" href="{{ route('admin.settings', ['tab' => 'account']) }}">My account</a>
    @if ($actor->canManageAdmins())
        <a class="tab {{ $tab === 'admins' ? 'active' : '' }}" href="{{ route('admin.settings', ['tab' => 'admins']) }}">Manage admins</a>
    @endif
</div>

@if ($tab === 'account')
    <div class="card" style="max-width:560px;">
        <form method="post" action="{{ route('admin.settings.account') }}">
            @csrf
            <div class="field">
                <label for="name">Name</label>
                <input id="name" name="name" value="{{ old('name', $actor->name) }}" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $actor->email) }}" required>
            </div>
            <div class="field">
                <label for="password">New password (optional)</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8">
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8">
            </div>
            <button class="btn btn-primary" type="submit">Save account</button>
        </form>
    </div>
@endif

@if ($tab === 'admins' && $actor->canManageAdmins())
    <div class="settings-grid">
        <div class="card">
            <h3 style="margin:0 0 12px;">Add admin</h3>
            <form method="post" action="{{ route('admin.settings.admins.store') }}">
                @csrf
                <div class="field">
                    <label for="new_name">Name</label>
                    <input id="new_name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label for="new_email">Email</label>
                    <input id="new_email" name="email" type="email" value="{{ old('email') }}" required>
                </div>
                <div class="field">
                    <label for="new_role">Role</label>
                    <select id="new_role" name="role" required>
                        <option value="admin" @selected(old('role', 'admin') === 'admin')>Admin</option>
                        <option value="moderator" @selected(old('role') === 'moderator')>Moderator</option>
                    </select>
                </div>
                <div class="field">
                    <label for="new_password">Password</label>
                    <input id="new_password" name="password" type="password" minlength="8" required>
                </div>
                <div class="field">
                    <label for="new_password_confirmation">Confirm password</label>
                    <input id="new_password_confirmation" name="password_confirmation" type="password" minlength="8" required>
                </div>
                <button class="btn btn-primary" type="submit">Create admin</button>
            </form>
        </div>

        <div>
            @foreach ($admins as $admin)
                <div class="card admin-card">
                    <h3>
                        {{ $admin->name }}
                        @if ($admin->isPrimary())
                            <span class="pill pill-main">Main admin</span>
                        @endif
                        @if ($admin->isRestricted())
                            <span class="pill pill-restricted">Restricted</span>
                        @endif
                    </h3>
                    <form method="post" action="{{ route('admin.settings.admins.update', $admin->id) }}">
                        @csrf
                        <div class="field">
                            <label>Name</label>
                            <input name="name" value="{{ old('name', $admin->name) }}" required>
                        </div>
                        <div class="field">
                            <label>Email</label>
                            <input name="email" type="email" value="{{ old('email', $admin->email) }}" required>
                        </div>
                        <div class="field">
                            <label>Role</label>
                            @if ($admin->isPrimary())
                                <input type="hidden" name="role" value="admin">
                                <input value="Admin (locked)" disabled>
                            @else
                                <select name="role" required>
                                    <option value="admin" @selected($admin->role === 'admin')>Admin</option>
                                    <option value="moderator" @selected($admin->role === 'moderator')>Moderator</option>
                                </select>
                            @endif
                        </div>
                        <div class="field">
                            <label>New password (optional)</label>
                            <input name="password" type="password" minlength="8" autocomplete="new-password">
                        </div>
                        <div class="field">
                            <label>Confirm password</label>
                            <input name="password_confirmation" type="password" minlength="8" autocomplete="new-password">
                        </div>

                        @if ($admin->isPrimary())
                            <p class="locked-note">Main admin cannot be restricted or demoted.</p>
                        @else
                            <label class="check-row">
                                <input type="checkbox" name="restricted" value="1" @checked($admin->isRestricted())
                                    @disabled($admin->id === $actor->id)>
                                Restrict this admin (blocks login)
                            </label>
                        @endif

                        <button class="btn btn-primary" type="submit">Save</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection
