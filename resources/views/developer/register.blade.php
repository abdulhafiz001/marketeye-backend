@extends('developer.layout')

@section('title', 'Register')

@section('content')
<div class="card" style="max-width:460px;margin:0 auto;">
    <h1>Create developer account</h1>
    <p class="muted">Separate from the Market Eye mobile app. Use this portal to get API keys for your platform.</p>
    <form method="post" action="{{ route('developer.register.post') }}">
        @csrf
        <label>Name</label>
        <input name="name" value="{{ old('name') }}" required>
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required>
        <label>Organization (optional)</label>
        <input name="organization" value="{{ old('organization') }}">
        <label>Password</label>
        <input type="password" name="password" required minlength="8">
        <label>Confirm password</label>
        <input type="password" name="password_confirmation" required minlength="8">
        <button class="btn" type="submit">Register</button>
    </form>
    <p class="muted" style="margin-top:16px;">Already registered? <a href="{{ route('developer.login') }}">Sign in</a></p>
</div>
@endsection
