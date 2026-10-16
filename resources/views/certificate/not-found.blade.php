@extends('layouts.app')
@section('title', 'Sertifikat Tidak Ditemukan')
@section('content')
<div class="cert-missing">
    <div class="em" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg></div>
    <h1>Sertifikat tidak ditemukan</h1>
    <p>Nomor sertifikat <strong>{{ $number }}</strong> tidak ditemukan atau belum diterbitkan.</p>
    <p>Pastikan alamat atau nomor sertifikat sudah benar.</p>
</div>
@endsection
