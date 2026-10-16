<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Program Pelatihan') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/rakit.css') }}?v=11">
</head>
<body>
    <header class="nav">
        <div class="nav-inner">
            <a href="{{ route('home') }}" class="brand"><img src="{{ asset('images/logo-karir-akademi.png') }}" alt="Karir Akademi by UCIC"></a>
            <button class="nav-toggle" type="button" aria-label="Menu" onclick="document.getElementById('menu').classList.toggle('open')">
                <svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <nav class="nav-links" id="menu">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home','registration.create','registration.store','registration.success') ? 'active' : '' }}">Home</a>
                <a href="{{ route('registration.search') }}" class="{{ request()->routeIs('registration.search','registration.find','registration.my-class') ? 'active' : '' }}">Kelas Saya</a>
                <!-- <a href="{{ route('subscription') }}" class="{{ request()->routeIs('subscription') ? 'active' : '' }}">Langganan</a> -->
                <a href="{{ route('partnership') }}" class="{{ request()->routeIs('partnership') ? 'active' : '' }}">Kerjasama Sekolah</a>
            </nav>
        </div>
    </header>

    <main>@yield('content')</main>

    <footer class="footer">
        {{-- Logo: letakkan berkas di public/images/logo-ucic.png dan public/images/logo-rakitai.png.
             Selama berkas belum ada, nama ditampilkan sebagai teks. --}}
        <div class="footer-logos">
            @foreach([['logo-ucic.png', 'UCIC'], ['logo-rakitai.png', 'Rakit AI']] as [$file, $label])
                @if(file_exists(public_path('images/'.$file)))
                    <img src="{{ asset('images/'.$file) }}" alt="{{ $label }}">
                @else
                    <span class="footer-logo-text">{{ $label }}</span>
                @endif
            @endforeach
        </div>
        <div>&copy; {{ date('Y') }} Karir Akademi by UCIC. Seluruh hak cipta dilindungi.</div>
    </footer>
</body>
</html>
