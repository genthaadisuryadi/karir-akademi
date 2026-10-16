@extends('layouts.app')
@section('title', 'Unduh Sertifikat')
@section('content')
<style>
    /* Halaman Unduh Sertifikat (memakai warna & komponen yang sama dengan Kelas Saya) */
    .cert-wrap{max-width:680px;margin:28px auto 90px;padding:0 24px}
    .cert-search .field{margin-bottom:16px}
    .cert-search input{text-transform:uppercase;letter-spacing:1.5px;font-weight:600}
    .cert-search input::placeholder{text-transform:none;letter-spacing:0;font-weight:400}

    .cert-card{background:#fff;border-radius:16px;box-shadow:0 8px 28px rgba(0,0,0,.08);padding:14px;margin-top:26px}
    .cert-frame{position:relative;border:2px solid var(--accent);border-radius:10px;padding:34px 28px 30px;text-align:center;background:linear-gradient(180deg,#fffbe6,#fff 55%)}
    .cert-frame::before{content:"";position:absolute;inset:6px;border:1px solid #f5df8a;border-radius:6px;pointer-events:none}
    .cert-seal{width:76px;height:76px;margin:0 auto 14px;border-radius:50%;background:var(--accent);display:grid;place-items:center;font-size:36px;box-shadow:0 0 0 6px #fff3bf,0 8px 18px rgba(230,175,0,.35)}
    .cert-seal svg{width:38px;height:38px;fill:none;stroke:#7c5700;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
    .cert-kicker{font-size:12px;letter-spacing:3px;text-transform:uppercase;color:var(--muted);font-weight:600}
    .cert-title{margin:6px 0 20px;font-size:24px;color:var(--primary)}
    .cert-sub{margin:0 0 6px;color:var(--muted);font-size:14px}
    .cert-name{display:inline-block;max-width:100%;margin:0 0 14px;padding:0 20px 8px;font-size:28px;line-height:1.3;font-weight:700;color:var(--ink);border-bottom:2px solid var(--accent);overflow-wrap:anywhere}
    .cert-program{margin:0 0 18px;font-size:17px;font-weight:600;line-height:1.5;color:var(--primary)}

    .cert-body{padding:24px 14px 12px}
    .cert-body .pd-stats{margin-bottom:24px}
    .cert-body .pd-stat .v.regno{color:var(--primary);letter-spacing:1px;overflow-wrap:anywhere}


    .cert-note{background:#f1f6fd;border:1px solid #dbe7f7;color:#1e3a5f;border-radius:10px;padding:12px 14px;font-size:14px;line-height:1.6;margin-bottom:18px}
    .cert-body .alert{margin-bottom:0}
    .cert-body .btn[disabled]{margin-top:4px}

    .cert-empty{margin-top:26px;background:#fff;border-radius:16px;box-shadow:0 8px 28px rgba(0,0,0,.08);padding:30px 28px;text-align:center}
    .cert-empty .em{font-size:40px;margin-bottom:6px}
    .cert-empty .em svg{width:40px;height:40px;fill:none;stroke:var(--primary);stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
    .cert-empty h2{margin:0 0 8px;font-size:19px}
    .cert-empty p{margin:0 0 16px;color:#4b5563;line-height:1.6;font-size:14.5px}
    .cert-empty a{color:var(--primary);font-weight:600;text-decoration:underline}

    @media(max-width:520px){
        .cert-frame{padding:28px 16px 24px}
        .cert-name{font-size:23px;padding:0 10px 8px}
        .cert-body{padding:20px 6px 8px}
    }
</style>

<section class="section-head">
    <h1>Unduh Sertifikat</h1>
    <p>Masukkan kode pendaftaran Anda (contoh: RKT-AB12CD) untuk memeriksa status sertifikat.</p>
</section>

<div class="cert-wrap">
    <section class="panel cert-search">
        <form method="POST" action="{{ route('certificate.lookup') }}" novalidate>
            @csrf
            <div class="field @error('code') has-error @enderror">
                <label for="code">Kode Pendaftaran <span class="req">*</span></label>
                <input id="code" name="code" type="text" value="{{ old('code', request('code')) }}" placeholder="RKT-XXXXXX" autocomplete="off" autocapitalize="characters" required>
                <div class="hint">Kode tampil di halaman sukses setelah Anda mendaftar.</div>
                @error('code')<div class="err">{{ $message }}</div>@enderror
            </div>
            <button class="btn" type="submit">Cari Sertifikat</button>
        </form>
    </section>

    @isset($searched)
        @if($registration)
            @php
                $statusClass = ['registered' => 'st-wait', 'certificate_issued' => 'st-ok', 'certificate_not_issued' => 'st-off'];
                $issued = $registration->isCertificateIssued() && filled($registration->certificateUrl());
            @endphp

            <article class="cert-card">
                <div class="cert-frame">
                    <div class="cert-seal" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 2 8l10 5 10-5-10-5Z"></path><path d="M6 10v5c3 3 9 3 12 0v-5M22 8v6"></path></svg></div>
                    <div class="cert-kicker">Karir Akademi by UCIC</div>
                    <h2 class="cert-title">Sertifikat Pelatihan</h2>
                    <p class="cert-sub">Diberikan kepada</p>
                    <div class="cert-name">{{ $registration->name }}</div>
                    <p class="cert-sub">sebagai peserta program</p>
                    <p class="cert-program">{{ $registration->program->title }}</p>
                    <span class="st {{ $statusClass[$registration->status] ?? 'st-wait' }}">{{ \App\Models\Registration::STATUSES[$registration->status] ?? $registration->status }}</span>
                </div>

                <div class="cert-body">
                    <div class="pd-stats">
                        <div class="pd-stat">
                            <div class="k">Nomor Registrasi</div>
                            <div class="v sm regno">{{ $registration->code }}</div>
                        </div>
                        <div class="pd-stat">
                            <div class="k">Nomor Sertifikat</div>
                            <div class="v sm regno">{{ $registration->certificate_number ?: '-' }}</div>
                        </div>
                    </div>

                    @if($issued)
                        <div class="cert-note">Selamat! Sertifikat Anda sudah terbit.</div>
                        <a class="btn" href="{{ $registration->certificateUrl(true) }}">Unduh Sertifikat</a>
                    @elseif($registration->status === 'certificate_not_issued')
                        <div class="alert">Sertifikat tidak diterbitkan untuk pendaftaran ini. Hubungi panitia bila ini keliru.</div>
                    @else
                        <div class="cert-note">Sertifikat dapat diunduh setelah diterbitkan.</div>
                        <button class="btn" type="button" disabled>Sertifikat Belum Terbit</button>
                    @endif
                </div>
            </article>
        @else
            <div class="cert-empty">
                <div class="em" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg></div>
                <h2>Kode pendaftaran tidak ditemukan</h2>
                <p>Pastikan penulisan kode sudah benar. Lupa kode Anda? Cari dengan email atau nomor HP di halaman <a href="{{ route('registration.search') }}">Kelas Saya</a>.</p>
            </div>
        @endif
    @endisset
</div>
@endsection
