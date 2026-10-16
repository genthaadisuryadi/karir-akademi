@extends('layouts.app')
@section('title', 'Kelas Saya')
@section('content')
@php
    $statusClass = ['registered' => 'st-wait', 'certificate_issued' => 'st-ok', 'certificate_not_issued' => 'st-off'];
@endphp
<section class="section-head">
    <h1>Kelas Saya</h1>
    <p>Halo, <strong>{{ $registrations->first()->name }}</strong>. Berikut kelas yang Anda daftar.</p>
</section>

<div class="pd-wrap">
    <div class="mc-list">
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

        <p style="text-align:center;margin:0">
            <a class="btn outline" style="display:inline-block;width:auto;padding:12px 30px" href="{{ route('registration.search') }}">Cari Lagi</a>
        </p>
    </div>
</div>
@endsection
