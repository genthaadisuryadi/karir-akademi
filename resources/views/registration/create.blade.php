@extends('layouts.app')
@section('title', 'Daftar '.$program->short_title)
@section('content')
<a href="{{ route('home') }}" class="back">&larr; Kembali ke Program Pelatihan</a>

<div class="form-wrap">
    {{-- Ringkasan program --}}
    <aside class="side">
        @if($program->poster)
            <div class="poster"><img src="{{ asset($program->poster) }}" alt="Poster {{ $program->title }}"></div>
        @endif
        <div class="side-body">
            <div class="card-top" style="margin-bottom:12px">
                <span class="pill red">{{ $program->price_label }}</span>
                <span class="pill dark">{{ $program->jp }} JP</span>
            </div>
            <h2>{{ $program->title }}</h2>
            <div class="info">
                <div><span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 11h18"></path></svg></span><span>{{ $program->date_label }}</span></div>
                @if($program->time_label)<div><span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg></span><span>{{ $program->time_label }}</span></div>@endif
                @if($program->location)<div><span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg></span><span>{{ $program->location }}</span></div>@endif
            </div>
        </div>
    </aside>

    {{-- Form --}}
    <section class="panel">
        <h1>Formulir Pendaftaran</h1>
        <p class="sub">Target peserta: <strong>{{ $program->audienceLabel() }}</strong>. Lengkapi data berikut untuk mendaftar. Kolom bertanda <span style="color:var(--primary)">*</span> wajib diisi.</p>

        @if($errors->any())
            <div class="alert">Terdapat {{ $errors->count() }} isian yang perlu diperbaiki. Silakan periksa kembali formulir di bawah.</div>
        @endif

        <form method="POST" action="{{ route('registration.store', $program) }}" novalidate>
            @csrf

            <!-- PENAMBAHAN WAJIB: Input target peserta yang dikirim ke Controller -->
            <input type="hidden" name="target_peserta" value="{{ $program->audienceLabel() }}">

            <div class="sec-title">Data Peserta</div>

            <div class="field @error('name') has-error @enderror">
                <label for="name">Nama Lengkap <span class="req">*</span></label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Nama beserta gelar" required>
                @error('name')<div class="err">{{ $message }}</div>@enderror
            </div>

            <div class="grid2">
                <div class="field @error('email') has-error @enderror">
                    <label for="email">Email <span class="req">*</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@email.com" required>
                    @error('email')<div class="err">{{ $message }}</div>@enderror
                </div>
                <div class="field @error('phone') has-error @enderror">
                    <label for="phone">Nomor WhatsApp <span class="req">*</span></label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="081234567890" required>
                    @error('phone')<div class="err">{{ $message }}</div>@enderror
                </div>
            </div>

            @unless($program->isForStudents())
                <div class="field @error('position') has-error @enderror">
                    <label for="position">Jabatan / Profesi (opsional)</label>
                    <input id="position" name="position" type="text" value="{{ old('position') }}" placeholder="Contoh: Guru Informatika">
                    @error('position')<div class="err">{{ $message }}</div>@enderror
                </div>
            @endunless

            <div class="sec-title" style="margin-top:28px">{{ $program->isForPublic() ? 'Data Instansi' : 'Data Sekolah' }}</div>

            <!-- PERUBAHAN: name="school_name" diganti menjadi name="nama_sekolah" atau name="instansi" -->
            @if($program->isForPublic())
                <div class="field @error('instansi') has-error @enderror">
                    <label for="instansi">Nama Instansi <span class="req">*</span></label>
                    <input id="instansi" name="instansi" type="text" value="{{ old('instansi') }}" placeholder="Contoh: UCIC atau nama instansi" required>
                    @error('instansi')<div class="err">{{ $message }}</div>@enderror
                </div>
            @else
                <div class="field @error('nama_sekolah') has-error @enderror">
                    <label for="nama_sekolah">Nama Sekolah <span class="req">*</span></label>
                    <input id="nama_sekolah" name="nama_sekolah" type="text" value="{{ old('nama_sekolah') }}" placeholder="Contoh: SMK Negeri 1 Cirebon" required>
                    @error('nama_sekolah')<div class="err">{{ $message }}</div>@enderror
                </div>
            @endif

            @if($program->isForStudents())
            <div class="grid2">
                <!-- PERUBAHAN: name="class_name" diganti name="kelas", dan placeholder lebih fleksibel -->
                <div class="field @error('kelas') has-error @enderror">
                    <label for="kelas">Kelas <span class="req">*</span></label>
                    <input id="kelas" name="kelas" type="text" value="{{ old('kelas') }}" placeholder="Bebas (Contoh: XII, 12, atau 12 IPA 3)" required>
                    @error('kelas')<div class="err">{{ $message }}</div>@enderror
                </div>
                <div class="field @error('city') has-error @enderror">
                    <label for="city">Kota/Kabupaten <span class="req">*</span></label>
                    <input id="city" name="city" type="text" value="{{ old('city') }}" placeholder="Contoh: Kota Cirebon" required>
                    @error('city')<div class="err">{{ $message }}</div>@enderror
                </div>
            </div>
            @else
                <div class="field @error('city') has-error @enderror">
                    <label for="city">Kota/Kabupaten <span class="req">*</span></label>
                    <input id="city" name="city" type="text" value="{{ old('city') }}" placeholder="Contoh: Kota Cirebon" required>
                    @error('city')<div class="err">{{ $message }}</div>@enderror
                </div>
            @endif

            <div class="field @error('motivation') has-error @enderror">
                <label for="motivation">Motivasi / Catatan (opsional)</label>
                <textarea id="motivation" name="motivation" placeholder="Ceritakan singkat tujuan Anda mengikuti pelatihan ini">{{ old('motivation') }}</textarea>
                @error('motivation')<div class="err">{{ $message }}</div>@enderror
            </div>

            <label class="check">
                <input type="checkbox" name="agree" value="1" @checked(old('agree'))>
                <span>Saya menyatakan data yang diisi benar dan bersedia mengikuti pelatihan sesuai jadwal.</span>
            </label>
            @error('agree')<div class="err" style="margin-top:-14px;margin-bottom:16px">{{ $message }}</div>@enderror

            <button type="submit" class="btn">Kirim Pendaftaran</button>
        </form>
    </section>
</div>
@endsection