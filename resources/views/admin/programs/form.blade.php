@extends('admin.layout')
@php
    $editing = $program->exists;
    $hasOld = count(old()) > 0;
    $isActive = $hasOld ? (bool) old('is_active') : (bool) ($program->is_active ?? true);
@endphp
@section('title', $editing ? 'Edit Kelas' : 'Tambah Kelas')
@section('heading', $editing ? 'Edit Kelas' : 'Tambah Kelas')
@section('content')
<div class="card">
    @if($errors->any())<div class="alert err">Ada {{ $errors->count() }} isian yang perlu diperbaiki.</div>@endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $editing ? route('admin.kelas.update', $program->id) : route('admin.kelas.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="fgrid">
            <div class="f full @error('title') has-error @enderror">
                <label for="title">Judul Kelas <span class="req">*</span></label>
                <input class="input" id="title" name="title" value="{{ old('title', $program->title) }}" required>
                @error('title')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f @error('short_title') has-error @enderror">
                <label for="short_title">Judul pada Kartu</label>
                <input class="input" id="short_title" name="short_title" value="{{ old('short_title', $program->short_title) }}">
                <div class="hint">Kosongkan untuk memakai judul kelas.</div>
                @error('short_title')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f @error('slug') has-error @enderror">
                <label for="slug">Slug URL</label>
                <input class="input" id="slug" name="slug" value="{{ old('slug', $program->slug) }}">
                <div class="hint">Kosongkan agar dibuat otomatis dari judul. Dipakai pada alamat form pendaftaran.</div>
                @error('slug')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f @error('audience') has-error @enderror">
                <label for="audience">Target Peserta <span class="req">*</span></label>
                <select class="input" id="audience" name="audience" required>
                    @foreach(\App\Models\Program::AUDIENCES as $value => $label)
                        <option value="{{ $value }}" @selected(old('audience', $program->audience ?? 'guru') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="hint">Siswa SMA/SMK: formulir meminta nama sekolah dan kelas. Guru: nama sekolah. Umum: nama instansi.</div>
                @error('audience')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            @php
                $prefixLocked = $editing && filled($program->certificate_prefix) && $program->registrations()->exists();
            @endphp
            <div class="f @error('certificate_prefix') has-error @enderror">
                <label for="certificate_prefix">Prefix Sertifikat <span class="req">*</span></label>
                <input class="input" id="certificate_prefix" name="certificate_prefix" value="{{ old('certificate_prefix', $program->certificate_prefix) }}" placeholder="bikin_ai_agent" pattern="[a-z0-9_]+" maxlength="60" autocapitalize="off" @readonly($prefixLocked) required>
                <div class="hint">
                    Harus unik antar kelas, tanpa spasi (huruf kecil, angka, dan _). Menjadi nomor sertifikat: <code>prefix-nomor_whatsapp</code>, contoh <code>bikin_ai_agent-083823051482</code>.
                    @if($prefixLocked) Dikunci karena kelas ini sudah memiliki pendaftar. @endif
                </div>
                @error('certificate_prefix')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f @error('price_label') has-error @enderror">
                <label for="price_label">Label Harga</label>
                <input class="input" id="price_label" name="price_label" value="{{ old('price_label', $program->price_label ?? 'GRATIS') }}" placeholder="GRATIS">
                @error('price_label')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f @error('date_label') has-error @enderror">
                <label for="date_label">Tanggal <span class="req">*</span></label>
                <input class="input" id="date_label" name="date_label" value="{{ old('date_label', $program->date_label) }}" placeholder="10 - 12 Agustus 2026" required>
                @error('date_label')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f @error('time_label') has-error @enderror">
                <label for="time_label">Jam</label>
                <input class="input" id="time_label" name="time_label" value="{{ old('time_label', $program->time_label) }}" placeholder="19.00 - 21.00 WIB">
                @error('time_label')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f @error('jp') has-error @enderror">
                <label for="jp">Jam Pelajaran (JP) <span class="req">*</span></label>
                <input class="input" id="jp" type="number" min="0" max="99" name="jp" value="{{ old('jp', $program->jp ?? 0) }}" required>
                @error('jp')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f @error('location') has-error @enderror">
                <label for="location">Lokasi</label>
                <input class="input" id="location" name="location" value="{{ old('location', $program->location) }}" placeholder="Online (Live) / UCIC Kampus 2">
                @error('location')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f @error('quota') has-error @enderror">
                <label for="quota">Kuota Pendaftar</label>
                <input class="input" id="quota" type="number" min="1" name="quota" value="{{ old('quota', $program->quota) }}">
                <div class="hint">Kosongkan jika tanpa batas. Jika penuh, tombol daftar menjadi "Kuota Penuh".</div>
                @error('quota')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f @error('sort_order') has-error @enderror">
                <label for="sort_order">Urutan Tampil</label>
                <input class="input" id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $program->sort_order ?? 0) }}">
                <div class="hint">Angka kecil tampil lebih dulu.</div>
                @error('sort_order')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f full @error('description') has-error @enderror">
                <label for="description">Deskripsi Kelas <span class="muted small"></span></label>
                <textarea class="input" id="description" name="description" rows="9" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13.5px" placeholder="Contoh:&#10;Transformasi peran guru menjadi **inovator teknologi**.&#10;&#10;## Materi&#10;- Dasar AI&#10;- Praktik IoT">{{ old('description', $program->description) }}</textarea>
                <div class="hint">Mendukung Markdown: <code>## Judul</code>, <code>**tebal**</code>, <code>*miring*</code>, daftar (<code>- item</code> / <code>1. item</code>), <code>[teks](https://...)</code>. Tag HTML dihapus otomatis. Tampil di bawah poster pada halaman detail kelas.</div>
                @error('description')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f full @error('benefit') has-error @enderror">
                <label for="benefit">Benefit <span class="muted small"> </span></label>
                <textarea class="input" id="benefit" name="benefit" rows="5" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13.5px" placeholder="- Sertifikat pelatihan 12 JP&#10;- Ebook untuk pengajaran di sekolah&#10;- Silabus untuk 1 tahun ajaran">{{ old('benefit', $program->benefit) }}</textarea>
                <div class="hint">Satu benefit per baris diawali <code>- </code>. Tampil sebagai daftar centang di sisi kanan halaman detail kelas.</div>
                @error('benefit')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f full @error('instructor') has-error @enderror">
                <label for="instructor">Pengajar</label>
                <input class="input" id="instructor" name="instructor" value="{{ old('instructor', $program->instructor) }}" placeholder="Nama pengajar (pisahkan dengan koma jika lebih dari satu)">
                <div class="hint">Tampil di halaman detail kelas.</div>
                @error('instructor')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f full @error('whatsapp_group_link') has-error @enderror">
                <label for="whatsapp_group_link">Tautan Kelas / Grup</label>
                <input class="input" id="whatsapp_group_link" type="url" name="whatsapp_group_link" value="{{ old('whatsapp_group_link', $program->whatsapp_group_link) }}" placeholder="https://contoh.com/tautan">
                <div class="hint">Masukkan tautan http atau https. Tautan hanya terlihat oleh peserta yang pendaftarannya dikonfirmasi.</div>
                @error('whatsapp_group_link')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f full @error('poster') has-error @enderror">
                <label for="poster">Poster</label>
                <input class="input" id="poster" type="file" name="poster" accept="image/png,image/jpeg,image/webp">
                <div class="hint">JPG/PNG/WEBP, maksimal 2 MB. Rasio ideal sekitar 4:5 (portrait).</div>
                @if($program->poster)
                    <div class="preview"><img src="{{ asset($program->poster) }}" alt="Poster saat ini"><span class="muted small">Poster saat ini. Unggah file baru untuk menggantinya.</span></div>
                @endif
                @error('poster')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            @foreach(['certificate_front' => 'Depan', 'certificate_back' => 'Belakang'] as $field => $side)
                <div class="f @error($field) has-error @enderror">
                    <label for="{{ $field }}">Template Sertifikat {{ $side }} (PNG) @unless(filled($program->{$field}))<span class="req">*</span>@endunless</label>
                    <input class="input" id="{{ $field }}" type="file" name="{{ $field }}" accept="image/png" @required(! filled($program->{$field}))>
                    <div class="hint">
                        @if($field === 'certificate_front')
                            PNG halaman depan. Nama, nomor, tanggal, dan QR Code ditempatkan otomatis di posisi tetap.
                        @else
                            PNG halaman belakang (statis, tidak ada data peserta).
                        @endif
                        Min. 1000 x 700 px, maks. 3600 x 2600 px, 8 MB.
                    </div>
                    @if(filled($program->{$field}))
                        <div class="muted small">Template sudah diunggah. Pilih berkas baru hanya jika ingin menggantinya.</div>
                    @endif
                    @error($field)<div class="ferr">{{ $message }}</div>@enderror
                </div>
            @endforeach

            <div class="f"><label class="chk"><input type="checkbox" name="is_active" value="1" @checked($isActive)> Aktif (tampil di halaman publik)</label></div>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $editing ? 'Simpan Perubahan' : 'Simpan Kelas' }}</button>
            <a class="btn ghost" href="{{ route('admin.kelas.index') }}">Batal</a>
        </div>
    </form>
</div>
@endsection
