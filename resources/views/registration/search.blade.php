@extends('layouts.app')
@section('title', 'Cek Kelas Saya')
@section('content')
<section class="section-head">
    <h1>Cek Kelas Saya</h1>
    <p>Masukkan email, nomor HP, atau nomor registrasi Anda untuk melihat detail kelas dan bergabung ke grup WhatsApp.</p>
</section>

<div class="narrow">
    <section class="panel">
        @if(session('error'))
            <div class="alert">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('registration.find') }}" novalidate>
            @csrf

            <div class="field @error('q') has-error @enderror">
                <label for="q">Email / Nomor HP <span class="req">*</span></label>
                <input id="q" name="q" type="text" value="{{ old('q') }}" placeholder="08123456789" autocomplete="off" autocapitalize="off" required autofocus>
                <div class="hint">Isi salah satu saja. Nomor registrasi tampil di halaman sukses setelah Anda mendaftar.</div>
                @error('q')<div class="err">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn">Cari Kelas Saya</button>
        </form>

        <p class="hint" style="text-align:center;margin-top:18px">Belum mendaftar? <a href="{{ route('home') }}" style="color:var(--primary);font-weight:600">Lihat program pelatihan</a></p>
    </section>
</div>
@endsection
