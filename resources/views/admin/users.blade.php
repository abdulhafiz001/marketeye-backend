@extends('admin.layout')

@section('title', 'Users')

@section('page_title', 'Manage users')

@section('content')
<p class="page-hint">Adjust roles and suspension. Be careful when changing admin access.</p>

<div class="card">
    <div style="overflow:auto;">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Points</th><th>Role</th><th>Suspended</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <form method="post" action="{{ route('admin.users.update', $u->id) }}">
                        @csrf
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->points }}</td>
                        <td style="min-width:130px;">
                            <select name="role" required>
                                <option value="user" @selected($u->role === 'user')>User</option>
                                <option value="moderator" @selected($u->role === 'moderator')>Moderator</option>
                                <option value="admin" @selected($u->role === 'admin')>Admin</option>
                            </select>
                        </td>
                        <td style="width:88px;"><input type="checkbox" name="banned" value="1" @checked($u->banned_at)></td>
                        <td><button class="btn" type="submit">Save</button></td>
                    </form>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
