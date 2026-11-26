@extends('layouts.app')
@section('title', 'Unduh Sertifikat')
@section('content')
<style>
    /* Halaman Unduh Sertifikat (memakai warna & komponen yang sama dengan Kelas Saya) */
    .cert-wrap{max-width:760px;margin:28px auto 90px;padding:0 24px}
    .cert-search .field{margin-bottom:16px}
    .cert-search input{letter-spacing:.5px;font-weight:600}
    .cert-search input::placeholder{letter-spacing:0;font-weight:400}

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

    /* Riwayat Sertifikat Pemateri & Moderator */
    .staff-head{margin:30px 0 14px;display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap}
    .staff-head h2{margin:0;font-size:20px;color:var(--ink)}
    .staff-head p{margin:4px 0 0;font-size:14px;color:var(--muted)}
    .staff-count{display:inline-block;padding:5px 12px;border-radius:999px;background:#eef4ff;color:var(--primary);font-size:13px;font-weight:700}

    .staff-list{display:flex;flex-direction:column;gap:18px}
    .staff-card{background:#fff;border:1px solid #e5ebf3;border-radius:16px;box-shadow:0 8px 24px rgba(15,23,42,.06);overflow:hidden;display:grid;grid-template-columns:230px 1fr;align-items:stretch}
    .staff-poster{background:#f8fafc;border-right:1px solid #edf2f7;padding:14px;display:flex;align-items:center;justify-content:center}
    .staff-poster img{width:100%;height:auto;max-width:100%;object-fit:contain;border-radius:10px;display:block;box-shadow:0 4px 12px rgba(15,23,42,.08)}
    .staff-poster-empty{width:100%;min-height:200px;display:grid;place-items:center;padding:16px;text-align:center;color:var(--muted);font-size:13px;font-weight:600}

    .staff-info{padding:20px 22px;display:flex;flex-direction:column;justify-content:space-between;gap:14px}
    .staff-badges{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:8px}
    .role-badge{display:inline-flex;align-items:center;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:.3px}
    .role-badge.pemateri{background:#e8f1ff;color:var(--primary);border:1px solid #c7dcff}
    .role-badge.moderator{background:#fff6d6;color:#8a5a00;border:1px solid #f5df8a}

    .staff-info h3{margin:0 0 6px;font-size:19px;line-height:1.35;color:var(--ink)}
    .staff-holder{margin:0 0 12px;font-size:14px;color:#374151}
    .staff-holder strong{color:var(--primary)}

    .staff-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 14px;padding:12px 14px;background:#f8fafc;border:1px solid #edf2f7;border-radius:10px;font-size:13.5px;color:#475569}
    .staff-meta .mk{font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;color:#64748b;margin-bottom:2px}
    .staff-meta .mv{font-weight:600;color:#1e293b;word-break:break-word}
    .staff-meta .full{grid-column:1/-1}

    .staff-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:4px}
    .staff-actions .btn{flex:1;min-width:150px;text-align:center;padding:11px 16px;font-size:14px}

    .cert-empty{margin-top:26px;background:#fff;border-radius:16px;box-shadow:0 8px 28px rgba(0,0,0,.08);padding:30px 28px;text-align:center}
    .cert-empty .em{font-size:40px;margin-bottom:6px}
    .cert-empty .em svg{width:40px;height:40px;fill:none;stroke:var(--primary);stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
    .cert-empty h2{margin:0 0 8px;font-size:19px}
    .cert-empty p{margin:0 0 16px;color:#4b5563;line-height:1.6;font-size:14.5px}
    .cert-empty a{color:var(--primary);font-weight:600;text-decoration:underline}

    @media(max-width:640px){
        .staff-card{grid-template-columns:1fr}
        .staff-poster{border-right:0;border-bottom:1px solid #edf2f7;padding:14px}
        .staff-poster img{max-height:360px;width:auto;max-width:100%}
        .staff-meta{grid-template-columns:1fr}
        .cert-frame{padding:28px 16px 24px}
        .cert-name{font-size:23px;padding:0 10px 8px}
        .cert-body{padding:20px 6px 8px}
    }
</style>

@php
    $staffEntries = $staffEntries ?? collect();
    $registrations = $registrations ?? (isset($registration) && $registration ? collect([$registration]) : collect());
@endphp

<section class="section-head">
    <h1>Unduh Sertifikat</h1>
    <p>Masukkan kode pendaftaran peserta (contoh: RKT-AB12CD) atau nomor WhatsApp (khusus Pemateri, Moderator, maupun Peserta) untuk mengunduh sertifikat.</p>
</section>

<div class="cert-wrap">
    <section class="panel cert-search">
        <form method="POST" action="{{ route('certificate.lookup') }}" novalidate>
            @csrf
            <div class="field @error('code') has-error @enderror">
                <label for="code">Kode Pendaftaran / Nomor WhatsApp <span class="req">*</span></label>
                <input id="code" name="code" type="text" value="{{ old('code', request('code')) }}" placeholder="Contoh: RKT-XXXXXX atau 081234567890" autocomplete="off" required>
                <div class="hint">Pemateri dan Moderator cukup memasukkan nomor WhatsApp untuk melihat seluruh riwayat bootcamp.</div>
                @error('code')<div class="err">{{ $message }}</div>@enderror
            </div>
            <button class="btn" type="submit">Cari Sertifikat</button>
        </form>
    </section>

    @isset($searched)
        @if($staffEntries->isNotEmpty())
            <div class="staff-head">
                <div>
                    <h2>Riwayat Sertifikat Pengajar &amp; Moderator</h2>
                    <p>Halo, <strong>{{ $staffEntries->first()->name }}</strong>. Pilih sertifikat bootcamp yang ingin Anda unduh.</p>
                </div>
                <span class="staff-count">{{ $staffEntries->count() }} Bootcamp</span>
            </div>

            <div class="staff-list">
                @foreach($staffEntries as $entry)
                    @php $program = $entry->program; @endphp
                    <article class="staff-card">
                        <div class="staff-poster">
                            @if($program->poster)
                                <img src="{{ asset($program->poster) }}" alt="Poster {{ $program->title }}" loading="lazy">
                            @else
                                <div class="staff-poster-empty">{{ $program->short_title }}</div>
                            @endif
                        </div>

                        <div class="staff-info">
                            <div>
                                <div class="staff-badges">
                                    <span class="role-badge {{ strtolower($entry->role) }}">{{ $entry->role }}</span>
                                    <span class="pill dark">{{ $program->jp }} JP</span>
                                </div>
                                <h3>{{ $program->title }}</h3>
                                <p class="staff-holder">Atas nama: <strong>{{ $entry->name }}</strong></p>

                                <div class="staff-meta">
                                    <div>
                                        <div class="mk">Jadwal</div>
                                        <div class="mv">{{ $program->date_label }}@if($program->time_label) · {{ $program->time_label }}@endif</div>
                                    </div>
                                    <div>
                                        <div class="mk">Lokasi</div>
                                        <div class="mv">{{ $program->location ?: 'Online / Menyesuaikan' }}</div>
                                    </div>
                                    <div class="full">
                                        <div class="mk">Nomor Sertifikat</div>
                                        <div class="mv" style="color:var(--primary)">{{ $entry->certificate_number }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="staff-actions">
                                @if($entry->has_template)
                                    <a class="btn" href="{{ $entry->download_url }}">Unduh Sertifikat</a>
                                    <a class="btn outline" href="{{ $entry->view_url }}" target="_blank" rel="noopener">Lihat PDF</a>
                                @else
                                    <button class="btn" type="button" disabled>Template Sertifikat Belum Tersedia</button>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @if($registrations->isNotEmpty())
            @php
                $statusClass = ['registered' => 'st-wait', 'certificate_issued' => 'st-ok', 'certificate_not_issued' => 'st-off'];
            @endphp

            @if($staffEntries->isNotEmpty())
                <div class="staff-head" style="margin-top:36px">
                    <div>
                        <h2>Riwayat Sertifikat Peserta</h2>
                        <p>Kelas yang Anda ikuti sebagai peserta.</p>
                    </div>
                    <span class="staff-count">{{ $registrations->count() }} Kelas</span>
                </div>
            @endif

            @foreach($registrations as $reg)
                @php
                    $issued = $reg->isCertificateIssued() && filled($reg->certificateUrl());
                @endphp

                <article class="cert-card">
                    <div class="cert-frame">
                        <div class="cert-seal" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 2 8l10 5 10-5-10-5Z"></path><path d="M6 10v5c3 3 9 3 12 0v-5M22 8v6"></path></svg></div>
                        <div class="cert-kicker">Karir Akademi by UCIC</div>
                        <h2 class="cert-title">Sertifikat Pelatihan</h2>
                        <p class="cert-sub">Diberikan kepada</p>
                        <div class="cert-name">{{ $reg->name }}</div>
                        <p class="cert-sub">sebagai peserta program</p>
                        <p class="cert-program">{{ $reg->program->title }}</p>
                        <span class="st {{ $statusClass[$reg->status] ?? 'st-wait' }}">{{ \App\Models\Registration::STATUSES[$reg->status] ?? $reg->status }}</span>
                    </div>

                    <div class="cert-body">
                        <div class="pd-stats">
                            <div class="pd-stat">
                                <div class="k">Nomor Registrasi</div>
                                <div class="v sm regno">{{ $reg->code }}</div>
                            </div>
                            <div class="pd-stat">
                                <div class="k">Nomor Sertifikat</div>
                                <div class="v sm regno">{{ $reg->certificate_number ?: '-' }}</div>
                            </div>
                        </div>

                        @if($issued)
                            <div class="cert-note">Selamat! Sertifikat Anda sudah terbit.</div>
                            <a class="btn" href="{{ $reg->certificateUrl(true) }}">Unduh Sertifikat</a>
                        @elseif($reg->status === 'certificate_not_issued')
                            <div class="alert">Sertifikat tidak diterbitkan untuk pendaftaran ini. Hubungi panitia bila ini keliru.</div>
                        @else
                            <div class="cert-note">Sertifikat dapat diunduh setelah diterbitkan.</div>
                            <button class="btn" type="button" disabled>Sertifikat Belum Terbit</button>
                        @endif
                    </div>
                </article>
            @endforeach
        @endif

        @if($staffEntries->isEmpty() && $registrations->isEmpty())
            <div class="cert-empty">
                <div class="em" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg></div>
                <h2>Data sertifikat tidak ditemukan</h2>
                <p>Pastikan kode pendaftaran atau nomor WhatsApp sudah benar. Anda juga dapat mengecek riwayat kelas di halaman <a href="{{ route('registration.search') }}">Kelas Saya</a>.</p>
            </div>
        @endif
    @endisset
</div>
@endsection