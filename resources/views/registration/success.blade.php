@extends('layouts.app')
@section('title', 'Pendaftaran Berhasil')
@section('content')
<div class="center-card">
    <div class="big" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4Z"></path><path d="M7 6H4v2a4 4 0 0 0 4 4M17 6h3v2a4 4 0 0 1-4 4"></path></svg></div>
    <h1>Pendaftaran Berhasil!</h1>
    <p>Terima kasih, <strong>{{ $registration->name }}</strong>. Simpan kode pendaftaran Anda berikut ini:</p>
    <div class="code">{{ $registration->code }}</div>

    <div class="summary">
        <dl>
            <dt>Program</dt><dd>{{ $registration->program->title }}</dd>
            <dt>Jadwal</dt><dd>{{ $registration->program->date_label }}@if($registration->program->time_label), {{ $registration->program->time_label }}@endif</dd>
            @if($registration->program->location)<dt>Lokasi</dt><dd>{{ $registration->program->location }}</dd>@endif
            <dt>{{ $registration->program->isForPublic() ? 'Instansi' : 'Sekolah' }}</dt><dd>{{ $registration->school_name ?: '-' }}@if($registration->class_name) (Kelas {{ $registration->class_name }})@endif</dd>
            <dt>Email</dt><dd>{{ $registration->email }}</dd>
            <dt>WhatsApp</dt><dd>{{ $registration->phone }}</dd>
        </dl>
    </div>

    <p style="font-size:14px">Status pendaftaran Anda: <strong>{{ \App\Models\Registration::STATUSES[$registration->status] ?? $registration->status }}</strong>.</p>

    @if($registration->program->hasExternalLink())
        <div class="actions" style="margin-bottom:8px">
            <a class="wa-btn" href="{{ $registration->program->whatsapp_group_link }}" target="_blank" rel="noopener noreferrer">Gabung Grup WhatsApp</a>
        </div>
        <p style="font-size:13px;margin-top:0">Mohon tidak membagikan tautan ini kepada orang yang tidak terdaftar.</p>
    @else
        <p style="font-size:14px">Tautan grup WhatsApp belum tersedia. Panitia akan menginformasikannya melalui WhatsApp/email.</p>
    @endif

    <div class="actions" style="margin-bottom:12px">
        <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('registration.my-class', now()->addMinutes(30), ['ids' => $registration->id]) }}" class="btn">Lihat Status &amp; Detail Kelas</a>
    </div>
    <p style="font-size:13px">Nanti Anda bisa mengeceknya lagi lewat menu <a href="{{ route('registration.search') }}" style="color:var(--primary);font-weight:600">Kelas Saya</a> dengan email, nomor HP, atau nomor registrasi di atas.</p>
    <div class="actions">
        <a href="{{ route('program.show', $registration->program) }}" class="btn outline">Kembali ke Halaman Kelas</a>
        <button type="button" class="btn" onclick="window.print()">Cetak Bukti</button>
    </div>
</div>
@endsection
