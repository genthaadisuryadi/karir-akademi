@extends('layouts.app')
@section('title', 'Kelas Saya')
@section('content')
@php
    $staffEntries = $staffEntries ?? collect();
    $registrations = $registrations ?? collect();
    $statusClass = ['registered' => 'st-wait', 'certificate_issued' => 'st-ok', 'certificate_not_issued' => 'st-off'];
    $displayName = $staffEntries->first()?->name ?? $registrations->first()?->name ?? 'Pengguna';
@endphp

<style>
    .mc-sec-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:0 0 16px}
    .mc-sec-head h2{margin:0;font-size:20px;color:var(--ink)}
    .mc-sec-count{display:inline-block;padding:5px 12px;border-radius:999px;background:#eef4ff;color:var(--primary);font-size:13px;font-weight:700}

    .staff-grid{display:flex;flex-direction:column;gap:20px;margin-bottom:32px}
    .staff-card{background:#fff;border:1px solid #e5ebf3;border-radius:16px;box-shadow:0 8px 24px rgba(15,23,42,.06);overflow:hidden;display:grid;grid-template-columns:280px 1fr;align-items:stretch}
    .staff-poster{background:#f8fafc;border-right:1px solid #edf2f7;padding:16px;display:flex;align-items:center;justify-content:center}
    .staff-poster img{width:100%;height:auto;max-width:100%;object-fit:contain;border-radius:10px;display:block;box-shadow:0 4px 14px rgba(15,23,42,.08)}
    .staff-poster-empty{width:100%;min-height:240px;display:grid;place-items:center;padding:20px;text-align:center;color:var(--muted);font-weight:600}

    .staff-body{padding:24px 26px;display:flex;flex-direction:column;justify-content:space-between;gap:16px}
    .staff-badges{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:10px}
    .role-badge{display:inline-flex;align-items:center;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700}
    .role-badge.pemateri{background:#e8f1ff;color:var(--primary);border:1px solid #c7dcff}
    .role-badge.moderator{background:#fff6d6;color:#8a5a00;border:1px solid #f5df8a}

    .staff-body h3{margin:0 0 6px;font-size:21px;line-height:1.35;color:var(--ink)}
    .staff-cert-no{margin:0 0 14px;font-size:13.5px;color:var(--muted);word-break:break-word}
    .staff-cert-no strong{color:var(--primary)}

    .staff-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:4px}
    .staff-actions .btn{width:auto;padding:11px 20px;font-size:14px}

    @media(max-width:720px){
        .staff-card{grid-template-columns:1fr}
        .staff-poster{border-right:0;border-bottom:1px solid #edf2f7;padding:16px}
        .staff-poster img{max-height:380px;width:auto;max-width:100%}
        .staff-body{padding:20px}
        .staff-actions .btn{flex:1;min-width:140px;text-align:center}
    }
</style>

<section class="section-head">
    <h1>Kelas Saya</h1>
    <p>Halo, <strong>{{ $displayName }}</strong>. Berikut riwayat bootcamp dan sertifikat Anda.</p>
</section>

<div class="pd-wrap">
    <div class="mc-list">

        {{-- RIWAYAT PEMATERI & MODERATOR --}}
        @if($staffEntries->isNotEmpty())
            <div class="mc-sec-head">
                <h2>Riwayat Pengajar &amp; Moderator</h2>
                <span class="mc-sec-count">{{ $staffEntries->count() }} Bootcamp</span>
            </div>

            <div class="staff-grid">
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

                        <div class="staff-body">
                            <div>
                                <div class="staff-badges">
                                    <span class="role-badge {{ strtolower($entry->role) }}">{{ $entry->role }}</span>
                                    <span class="pill dark">{{ $program->jp }} JP</span>
                                    <span class="st st-ok">Sertifikat Tersedia</span>
                                </div>

                                <h3>{{ $program->title }}</h3>
                                <p class="staff-cert-no">Nama di Sertifikat: <strong>{{ $entry->name }}</strong> &middot; No: <strong>{{ $entry->certificate_number }}</strong></p>

                                <div class="pd-stats" style="margin-bottom:0">
                                    <div class="pd-stat">
                                        <div class="k">Peran</div>
                                        <div class="v sm">{{ $entry->role }}</div>
                                    </div>
                                    <div class="pd-stat">
                                        <div class="k">JP (Jam Pelajaran)</div>
                                        <div class="v sm">{{ $program->jp }} JP</div>
                                    </div>
                                    <div class="pd-stat">
                                        <div class="k">Jadwal</div>
                                        <div class="v sm">{{ $program->date_label }}@if($program->time_label)<br>{{ $program->time_label }}@endif</div>
                                    </div>
                                    <div class="pd-stat">
                                        <div class="k">Lokasi</div>
                                        <div class="v sm">{{ $program->location ?: 'Online / Menyesuaikan' }}</div>
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
                                <a class="btn outline" href="{{ route('program.show', $program) }}">Detail Bootcamp</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        {{-- RIWAYAT PESERTA --}}
        @if($registrations->isNotEmpty())
            @if($staffEntries->isNotEmpty())
                <div class="mc-sec-head" style="margin-top:12px">
                    <h2>Kelas yang Anda Ikuti sebagai Peserta</h2>
                    <span class="mc-sec-count">{{ $registrations->count() }} Kelas</span>
                </div>
            @endif

            @foreach($registrations as $reg)
                @php
                    $program = $reg->program;
                @endphp

                {{-- Tata letak sama dengan halaman detail kelas di tampilan depan (program.show) --}}
                <article class="pd">
                    {{-- Poster: kolom kiri --}}
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

                    {{-- Kolom kanan: status, judul, info kelas, benefit, grup WhatsApp --}}
                    <aside class="pd-side">
                        <div class="pd-badges">
                            <span class="pill red">{{ $program->price_label }}</span>
                            <span class="st {{ $statusClass[$reg->status] ?? 'st-wait' }}">{{ \App\Models\Registration::STATUSES[$reg->status] ?? $reg->status }}</span>
                        </div>
                        <h1>{{ $program->title }}</h1>
                        <p class="reg-code" style="margin:-8px 0 20px">Nomor Registrasi: <strong>{{ $reg->code }}</strong></p>

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
                            <div class="pd-stat">
                                <div class="k">Jadwal</div>
                                <div class="v sm">{{ $program->date_label }}@if($program->time_label)<br>{{ $program->time_label }}@endif</div>
                            </div>
                            <div class="pd-stat">
                                <div class="k">Lokasi</div>
                                @if(filled($program->location))
                                    <div class="v sm">{{ $program->location }}</div>
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

                        {{-- Tautan grup WhatsApp selalu tampil untuk peserta yang sudah mendaftar, apa pun statusnya. --}}
                        <div class="sec-title">Grup WhatsApp</div>
                        @if($program->hasExternalLink())
                            <a class="pd-link" href="{{ $program->whatsapp_group_link }}" target="_blank" rel="noopener noreferrer">{{ $program->whatsapp_group_link }}</a>
                            <p class="hint">Mohon tidak membagikan tautan ini kepada orang yang tidak terdaftar.</p>
                        @else
                            <p class="muted">Tautan grup belum tersedia. Panitia akan menginformasikannya melalui WhatsApp/email.</p>
                        @endif

                        <div class="sec-title">Sertifikat</div>
                        @if($reg->isCertificateIssued() && filled($reg->certificateUrl()))
                            <p class="muted">Sertifikat Anda sudah terbit.</p>
                            <a class="btn" href="{{ $reg->certificateUrl(true) }}">Unduh Sertifikat</a>
                        @elseif($reg->status === 'certificate_not_issued')
                            <p class="muted">Sertifikat tidak diterbitkan untuk pendaftaran ini. Hubungi panitia bila ini keliru.</p>
                        @else
                            <p class="muted">Sertifikat dapat diunduh di sini setelah diterbitkan.</p>
                        @endif

                        <div class="pd-cta">
                            @if($program->hasExternalLink())
                                <a class="wa-btn" href="{{ $program->whatsapp_group_link }}" target="_blank" rel="noopener noreferrer">
                                    Buka Grup WhatsApp
                                </a>
                            @endif
                            <a href="{{ route('program.show', $program) }}" class="btn outline">Lihat Halaman Kelas</a>
                        </div>
                    </aside>
                </article>
            @endforeach
        @endif

        <p style="text-align:center;margin:0">
            <a class="btn outline" style="display:inline-block;width:auto;padding:12px 30px" href="{{ route('registration.search') }}">Cari Lagi</a>
        </p>
    </div>
</div>
@endsection