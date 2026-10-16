<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk Admin - {{ config('app.name') }}</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="login-body">
    <div class="login-card">
        <img src="{{ asset('images/logo-karir-akademi.png') }}" alt="Karir Akademi">
        <h1>Masuk Admin</h1>
        <p class="sub">Kelola kelas, pendaftar, dan pengguna.</p>

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="f @error('email') has-error @enderror">
                <label for="email">Email</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email')<div class="ferr">{{ $message }}</div>@enderror
            </div>
            <div class="f @error('password') has-error @enderror">
                <label for="password">Kata Sandi</label>
                <input class="input" id="password" type="password" name="password" required autocomplete="current-password">
                @error('password')<div class="ferr">{{ $message }}</div>@enderror
            </div>
            <label class="chk" style="margin-bottom:18px"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
            <button class="btn" type="submit">Masuk</button>
        </form>
        <a class="back" href="{{ route('home') }}">&larr; Kembali ke situs</a>
    </div>
</body>
</html>
