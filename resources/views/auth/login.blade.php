<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in - Aling Leng's Sari-Sari Store</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body class="auth-page">
    <main class="auth-card">
        <a href="{{ route('login') }}" class="brand auth-brand">
            <span class="brand-name">Aling Leng's</span>
            <span class="brand-sub">Sari-Sari Store POS</span>
        </a>
        <div class="page-header">
            <h1>Welcome back</h1>
            <p>Sign in to continue to the store system.</p>
        </div>
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('login') }}" class="auth-form">
            @csrf
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            <label class="auth-remember">
                <input type="checkbox" name="remember" value="1">
                Remember me
            </label>
            <button type="submit" class="btn btn-primary btn-block">Sign in</button>
        </form>
    </main>
</body>
</html>
