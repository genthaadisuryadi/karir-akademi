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
    <style>
        /* Tambahan style untuk merapikan footer */
        .footer-custom {
            background-color: #ffffff; /* Ubah background jadi putih */
            color: #374151; /* Ubah warna teks jadi gelap */
            padding: 40px 20px;
            text-align: center;
            margin-top: 50px;
            border-top: 1px solid #e5e7eb; /* Garis batas atas tipis agar elegan */
        }
        .footer-slogan {
            font-size: 16px;
            margin-bottom: 25px;
            line-height: 1.6;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        .footer-copyright {
            font-size: 14px;
            color: #6b7280; /* Teks abu-abu agar tidak terlalu pekat */
            margin-bottom: 25px;
        }
        .footer-logos-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 35px; /* Jarak antar logo diperlebar sedikit */
            margin-top: 15px;
        }
        .footer-logos-wrap img {
            height: 45px; /* Tinggi standar untuk logo UCIC */
            width: auto;
            object-fit: contain;
        }
        /* Trik khusus agar logo Rakit AI proporsional dan tidak kebesaran */
        .footer-logos-wrap img[alt="Rakit AI"] {
            height: 28px; 
        }
        .footer-logo-text {
            font-weight: 600;
            color: #374151;
            background: #f3f4f6;
            padding: 8px 16px;
            border-radius: 4px;
        }
    </style>
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

    <!-- Footer yang telah diperbaiki -->
    <footer class="footer-custom">
        
        <div class="footer-copyright">
            &copy; {{ date('Y') }} Karir Akademi. Seluruh hak cipta dilindungi.
        </div>

        <div class="footer-logos-wrap">
            @foreach([['logo-ucic.png', 'UCIC'], ['logo.png', 'Rakit AI']] as [$file, $label])
                @if(file_exists(public_path('images/'.$file)))
                    <img src="{{ asset('images/'.$file) }}" alt="{{ $label }}">
                @else
                    <span class="footer-logo-text">{{ $label }}</span>
                @endif
            @endforeach
        </div>
    </footer>
</body>
</html>