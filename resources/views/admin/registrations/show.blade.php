@extends('admin.layout')
@section('title', 'Detail Pendaftar')
@section('heading', 'Detail Pendaftar')
@section('content')
<div class="card">
    <div class="toolbar">
        <div><span class="muted small">Kode pendaftaran</span><div style="font-size:22px;font-weight:700;color:var(--primary)">{{ $registration->code }}</div></div>
        <a class="btn ghost" href="{{ route('admin.pendaftar.index') }}">&larr; Kembali</a>
    </div>

    <dl class="detail">
        <dt>Kelas</dt><dd>{{ $registration->program->title }}</dd>
        <dt>Nama</dt><dd>{{ $registration->name }}</dd>
        <dt>Email</dt><dd><a href="mailto:{{ $registration->email }}" style="color:var(--primary)">{{ $registration->email }}</a></dd>
        <dt>WhatsApp</dt><dd>{{ $registration->phone }}</dd>
        <dt>Target Peserta</dt><dd>{{ $registration->program->audienceLabel() }}</dd>
        <dt>{{ $registration->program->isForPublic() ? 'Instansi' : 'Sekolah' }}</dt><dd>{{ $registration->school_name ?: '-' }}</dd>
        @if($registration->class_name)<dt>Kelas</dt><dd>{{ $registration->class_name }}</dd>@endif
        <dt>Kota/Kabupaten</dt><dd>{{ $registration->city }}</dd>
        <!-- <dt>Jabatan/Mapel</dt><dd>{{ $registration->position ?: '-' }}</dd> -->
        <dt>Motivasi</dt><dd>{{ $registration->motivation ?: '-' }}</dd>
        <dt>Status</dt><dd>{{ \App\Models\Registration::STATUSES[$registration->status] ?? $registration->status }}</dd>
        <dt>No. Sertifikat</dt><dd>{{ $registration->certificate_number ?: '-' }}</dd>
        <dt>Waktu daftar</dt><dd>{{ $registration->created_at->format('d M Y H:i') }}</dd>
    </dl>

    @if($registration->isCertificateIssued() && $registration->certificateUrl())
        <a class="btn yellow" href="{{ $registration->certificateUrl() }}" target="_blank" rel="noopener">Lihat PDF Sertifikat</a>
        @if($registration->certificate_issued_at)<div class="muted small" style="margin-top:6px">Terbit {{ $registration->certificate_issued_at->format('d M Y H:i') }}</div>@endif
    @endif
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.pendaftar.destroy', $registration->id) }}" onsubmit="return confirm('Hapus data pendaftar ini?')">
        @csrf @method('DELETE')
        <button class="btn danger" type="submit">Hapus Pendaftar</button>
    </form>
</div>
@endsection
