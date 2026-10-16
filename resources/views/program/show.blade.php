@extends('layouts.app')
@section('title', $program->short_title)
@section('content')
<a href="{{ route('home') }}" class="back">&larr; Kembali ke Program Pelatihan</a>

<div class="pd-wrap">
<div class="pd">
    {{-- Poster: mepet ke atas, kolom kiri --}}
    @if($program->poster)
        <div class="pd-poster">
            <img src="{{ asset($program->poster) }}" alt="Poster {{ $program->title }}">
        </div>
    @endif

    {{-- Deskripsi (Markdown): di bawah poster --}}
    <section class="pd-desc">
        <div class="sec-title">Deskripsi Kelas</div>
        @if(filled($program->description))
            <div class="md">{{ $program->descriptionHtml() }}</div>
        @else
            <p class="muted">Deskripsi kelas belum tersedia.</p>
        @endif
    </section>

    {{-- Kolom kanan: judul, JP, pengajar, benefit, grup WhatsApp --}}
    <aside class="pd-side">
        <div class="pd-badges">
            <span class="pill red">{{ $program->price_label }}</span>
            @if($isRegistered)
                <span class="pd-registered">✓ Anda sudah terdaftar</span>
            @endif
        </div>
        <h1>{{ $program->title }}</h1>

        <div class="pd-stats">
            <div class="pd-stat">
                <div class="k">JP (Jam Pelajaran)</div>
                <div class="v">{{ $program->jp }} JP</div>
            </div>
            <div class="pd-stat">
                <div class="k">Pengajar</div>
                @if(filled($program->instructor))
                    <div class="v sm">{{ $program->instructor }}</div>
                @else
                    <div class="v sm muted-v">Belum ditentukan</div>
                @endif
            </div>
        </div>

        <div class="sec-title">Benefit</div>
        @if(filled($program->benefit))
            <div class="md md-check">{{ $program->benefitHtml() }}</div>
        @else
            <p class="muted">Informasi benefit belum tersedia.</p>
        @endif

        <div class="sec-title" style="margin-bottom: 12px;">Grup WhatsApp</div>

        @if($isRegistered)
            @if($program->hasExternalLink())
                <a class="btn" href="{{ $program->whatsapp_group_link }}" target="_blank" rel="noopener noreferrer">
                    Buka Grup WhatsApp
                </a>
                <p class="hint" style="font-size: 14px; color: #6c788a; margin: 12px 0 0; line-height: 1.5;">Mohon tidak membagikan tautan ini kepada orang yang tidak terdaftar.</p>
            @else
                <button disabled style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; background-color: #dde3ec; color: #6c788a; border: 1px solid #cdd5df; border-bottom: 4px solid #b7c3d0; border-radius: 10px; padding: 14px; font-weight: 700; font-size: 16px; cursor: not-allowed; margin-bottom: 12px;">
                    Tautan Belum Tersedia
                </button>
                <p class="muted" style="font-size: 14px; color: #6c788a; margin: 0; line-height: 1.5;">Panitia akan menginformasikannya segera.</p>
            @endif
        @else
            <button disabled style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; background-color: #dde3ec; color: #6c788a; border: 1px solid #cdd5df; border-bottom: 4px solid #b7c3d0; border-radius: 10px; padding: 14px; font-weight: 700; font-size: 16px; cursor: not-allowed; margin-bottom: 12px;">
                Grup WhatsApp Terkunci
            </button>
            <p class="hint" style="font-size: 14px; color: #6c788a; margin: 0; line-height: 1.5;">
                Tautan grup WhatsApp terbuka setelah Anda mendaftar. Sudah mendaftar? <a href="{{ route('registration.search') }}" style="color: #0266c8; font-weight: 700; text-decoration: none;">Cek di Kelas Saya</a>.
            </p>
        @endif

        <div class="pd-cta">
            @if($isRegistered)
                <a href="{{ route('registration.search') }}" class="btn outline">Lihat Kelas Saya</a>
            @elseif($program->isFull())
                <button class="btn" disabled>Kuota Penuh</button>
            @else
                <a href="{{ route('registration.create', $program) }}" class="btn">Daftar Sekarang</a>
            @endif
        </div>
    </aside>
</div>
</div>
@endsection
