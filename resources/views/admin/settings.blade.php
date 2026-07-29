@extends('admin.layout')

@section('title', 'Settings')

@section('page_title', 'Settings')

@push('styles')
<style>
    .settings-tabs {
        display: flex;
        gap: 6px;
        margin: 0 0 22px;
        padding: 6px;
        background: #0B1220;
        border: 1px solid var(--border);
        border-radius: 14px;
        width: fit-content;
        max-width: 100%;
        flex-wrap: wrap;
    }
    .settings-tabs a {
        display: inline-flex;
        align-items: center;
        padding: 10px 16px;
        border-radius: 10px;
        color: var(--muted);
        text-decoration: none;
        font-weight: 800;
        font-size: 13px;
        border: 1px solid transparent;
    }
    .settings-tabs a:hover { color: var(--text); background: rgba(255,255,255,.03); }
    .settings-tabs a.active {
        color: #fff;
        background: rgba(34,197,94,.14);
        border-color: rgba(34,197,94,.28);
    }

    .panel-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .panel-head h2 { margin: 0; font-size: 16px; font-weight: 900; letter-spacing: -0.02em; }
    .panel-head p { margin: 4px 0 0; color: var(--muted); font-size: 13px; font-weight: 600; line-height: 1.45; max-width: 46rem; }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    @media (max-width: 720px) { .form-row { grid-template-columns: 1fr; } }
    .field { margin-bottom: 0; }
    .field + .field { } /* spacing via grid gap */
    .stack-fields { display: grid; gap: 12px; }
    .actions { display:flex; gap:10px; flex-wrap:wrap; margin-top: 14px; }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 900;
        border: 1px solid var(--border);
        color: var(--muted);
        white-space: nowrap;
    }
    .badge-main { color:#86EFAC; border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.12); }
    .badge-restricted { color:#FCA5A5; border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.12); }
    .badge-role { color:#FDE68A; border-color: rgba(253,230,138,.25); background: rgba(253,230,138,.08); }

    .admins-layout {
        display: grid;
        grid-template-columns: minmax(280px, 320px) minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
    @media (max-width: 1100px) { .admins-layout { grid-template-columns: 1fr; } }

    .add-card { position: sticky; top: 16px; }
    .team-list { display: grid; gap: 12px; }

    .admin-row {
        border: 1px solid var(--border);
        border-radius: 14px;
        background: rgba(0,0,0,.12);
        padding: 14px 16px;
    }
    .admin-row-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }
    .admin-identity h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 900;
    }
    .admin-identity .email {
        margin-top: 3px;
        color: var(--muted);
        font-size: 12px;
        font-weight: 600;
    }
    .admin-identity .tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .admin-fields {
        display: grid;
        grid-template-columns: 1.2fr 1.4fr 0.9fr 1.1fr auto;
        gap: 12px;
        align-items: end;
    }
    @media (max-width: 1100px) {
        .admin-fields { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 640px) {
        .admin-fields { grid-template-columns: 1fr; }
    }

    .access-box {
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 10px 12px;
        min-height: 42px;
        display: flex;
        align-items: center;
        background: #0B1220;
    }
    .access-box label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        text-transform: none;
        letter-spacing: 0;
        font-size: 12px;
        font-weight: 700;
        color: var(--muted);
        cursor: pointer;
    }
    .access-box input { width: auto; }
    .access-locked {
        color: #FBBF24;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.35;
    }

    .pw-pair { display: grid; gap: 8px; }
    .account-card { max-width: 560px; }
    .create-btn { width: 100%; margin-top: 4px; }
</style>
@endpush

@section('content')
<div class="settings-tabs">
    <a class="{{ $tab === 'account' ? 'active' : '' }}" href="{{ route('admin.settings', ['tab' => 'account']) }}">My account</a>
    @if ($actor->canManageAdmins())
        <a class="{{ $tab === 'admins' ? 'active' : '' }}" href="{{ route('admin.settings', ['tab' => 'admins']) }}">Manage admins</a>
    @endif
</div>

@if ($tab === 'account')
    <div class="card account-card">
        <div class="panel-head">
            <div>
                <h2>Your account</h2>
                <p>Update how you appear in the admin panel and optionally change your password.</p>
            </div>
            @if ($actor->isPrimary())
                <span class="badge badge-main">Main admin</span>
            @endif
        </div>

        <form method="post" action="{{ route('admin.settings.account') }}">
            @csrf
            <div class="form-row" style="margin-bottom:12px;">
                <div class="field">
                    <label for="name">Name</label>
                    <input id="name" name="name" value="{{ old('name', $actor->name) }}" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $actor->email) }}" required>
                </div>
            </div>
            <div class="form-row">
                <div class="field">
                    <label for="password">New password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" placeholder="Leave blank to keep current">
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8">
                </div>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Save changes</button>
            </div>
        </form>
    </div>
@endif

@if ($tab === 'admins' && $actor->canManageAdmins())
    <div class="admins-layout">
        <div class="card add-card">
            <div class="panel-head">
                <div>
                    <h2>Add admin</h2>
                    <p>Create a teammate account. Admins manage the panel; moderators help with submissions.</p>
                </div>
            </div>
            <form method="post" action="{{ route('admin.settings.admins.store') }}" class="stack-fields">
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
                <button class="btn btn-primary create-btn" type="submit">Create admin</button>
            </form>
        </div>

        <div class="card">
            <div class="panel-head">
                <div>
                    <h2>Admin team</h2>
                    <p>
                        {{ $admins->count() }} account{{ $admins->count() === 1 ? '' : 's' }}.
                        Edit a row and click Save. The main admin cannot be restricted or demoted.
                    </p>
                </div>
            </div>

            <div class="team-list">
                @foreach ($admins as $admin)
                    <form method="post" action="{{ route('admin.settings.admins.update', $admin->id) }}" class="admin-row">
                        @csrf
                        <div class="admin-row-top">
                            <div class="admin-identity">
                                <h3>{{ $admin->name }}</h3>
                                <div class="email">{{ $admin->email }}</div>
                                <div class="tags">
                                    @if ($admin->isPrimary())
                                        <span class="badge badge-main">Main admin</span>
                                    @endif
                                    <span class="badge badge-role">{{ ucfirst($admin->role) }}</span>
                                    @if ($admin->isRestricted())
                                        <span class="badge badge-restricted">Restricted</span>
                                    @endif
                                </div>
                            </div>
                            <button class="btn btn-primary" type="submit">Save</button>
                        </div>

                        <div class="admin-fields">
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
                                    <input value="Admin" disabled>
                                @else
                                    <select name="role" required>
                                        <option value="admin" @selected($admin->role === 'admin')>Admin</option>
                                        <option value="moderator" @selected($admin->role === 'moderator')>Moderator</option>
                                    </select>
                                @endif
                            </div>
                            <div class="field">
                                <label>New password</label>
                                <div class="pw-pair">
                                    <input name="password" type="password" minlength="8" autocomplete="new-password" placeholder="Optional">
                                    <input name="password_confirmation" type="password" minlength="8" autocomplete="new-password" placeholder="Confirm">
                                </div>
                            </div>
                            <div class="field">
                                <label>Access</label>
                                <div class="access-box">
                                    @if ($admin->isPrimary())
                                        <span class="access-locked">Main admin — cannot restrict</span>
                                    @elseif ($admin->id === $actor->id)
                                        <span class="access-locked">You can’t restrict yourself</span>
                                    @else
                                        <label>
                                            <input type="checkbox" name="restricted" value="1" @checked($admin->isRestricted())>
                                            Restrict login
                                        </label>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
@endif
@endsection
