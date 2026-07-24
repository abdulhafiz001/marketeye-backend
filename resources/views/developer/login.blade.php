@extends('developer.layout')

@section('title', 'Login')

@section('content')
<div class="card" style="max-width:420px;margin:0 auto;">
    <h1>Developer sign in</h1>
    <p class="muted">Access your API keys and usage limits.</p>
    <form method="post" action="{{ route('developer.login.post') }}">
        @csrf
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button class="btn" type="submit">Sign in</button>
    </form>
    <p class="muted" style="margin-top:16px;">New here? <a href="{{ route('developer.register') }}">Create a developer account</a></p>
</div>
@endsection
