@extends('admin.layout')
@php
    $editing = $program->exists;
    $hasOld = count(old()) > 0;
    $isActive = $hasOld ? (bool) old('is_active') : (bool) ($program->is_active ?? true);
    $isClosed = $hasOld ? (bool) old('is_closed') : (bool) ($program->is_closed ?? false);

    $showIns1 = old('instructor', $program->instructor) || old('instructor_phone', $program->instructor_phone) || $errors->has('instructor') || $errors->has('instructor_phone');
    $showIns2 = old('instructor_2', $program->instructor_2) || old('instructor_2_phone', $program->instructor_2_phone) || $errors->has('instructor_2') || $errors->has('instructor_2_phone');
    $showIns3 = old('instructor_3', $program->instructor_3) || old('instructor_3_phone', $program->instructor_3_phone) || $errors->has('instructor_3') || $errors->has('instructor_3_phone');
    
    $showMod1 = old('moderator', $program->moderator) || old('moderator_phone', $program->moderator_phone) || $errors->has('moderator') || $errors->has('moderator_phone');
    $showMod2 = old('moderator_2', $program->moderator_2) || old('moderator_2_phone', $program->moderator_2_phone) || $errors->has('moderator_2') || $errors->has('moderator_2_phone');
    $showMod3 = old('moderator_3', $program->moderator_3) || old('moderator_3_phone', $program->moderator_3_phone) || $errors->has('moderator_3') || $errors->has('moderator_3_phone');
@endphp
@section('title', $editing ? 'Edit Kelas' : 'Tambah Kelas')
@section('heading', $editing ? 'Edit Kelas' : 'Tambah Kelas')
@section('content')
<div class="card">
    @if($errors->any())<div class="alert err">Ada {{ $errors->count() }} isian yang perlu diperbaiki.</div>@endif

    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.kelas.update', $program->id) : route('admin.kelas.store') }}">
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
                <div class="hint">Siswa/Mahasiswa: meminta institusi dan prodi/kelas. Guru: nama sekolah. Umum: nama instansi.</div>
                @error('audience')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            @php $prefixLocked = $editing && filled($program->certificate_prefix) && $program->registrations()->exists(); @endphp
            <div class="f @error('certificate_prefix') has-error @enderror">
                <label for="certificate_prefix">Prefix Sertifikat <span class="req">*</span></label>
                <input class="input" id="certificate_prefix" name="certificate_prefix" value="{{ old('certificate_prefix', $program->certificate_prefix) }}" placeholder="bikin_ai_agent" pattern="[a-z0-9_]+" maxlength="60" autocapitalize="off" @readonly($prefixLocked) required>
                <div class="hint">Harus unik antar kelas, tanpa spasi. Menjadi nomor sertifikat: <code>prefix-nomor_whatsapp</code>.</div>
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
                <label for="description">Deskripsi Kelas</label>
                <textarea class="input" id="description" name="description" rows="9" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13.5px">{{ old('description', $program->description) }}</textarea>
                <div class="hint">Mendukung Markdown. Tag HTML dihapus otomatis. Tampil di halaman detail kelas.</div>
                @error('description')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <div class="f full @error('benefit') has-error @enderror">
                <label for="benefit">Benefit</label>
                <textarea class="input" id="benefit" name="benefit" rows="5" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13.5px">{{ old('benefit', $program->benefit) }}</textarea>
                <div class="hint">Satu benefit per baris diawali <code>- </code>.</div>
                @error('benefit')<div class="ferr">{{ $message }}</div>@enderror
            </div>

            <!-- BAGIAN PENGAJAR DINAMIS -->
            <div class="f full" style="margin-top: 15px; margin-bottom: 5px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">
                    <div style="font-weight: bold; font-size: 16px;">Susunan Pengajar / Panitia</div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" id="btn-add-ins" class="btn sm ghost" style="background-color: transparent; border: 1px solid var(--primary); color: var(--primary);" onclick="addIns()">+ Pemateri</button>
                        <button type="button" id="btn-add-mod" class="btn sm ghost" style="background-color: transparent; border: 1px solid var(--primary); color: var(--primary);" onclick="addMod()">+ Moderator</button>
                    </div>
                </div>
            </div>
            
            <!-- Pemateri 1 -->
            <div id="block-ins-1" class="f full" style="display: {{ $showIns1 ? 'block' : 'none' }}; background: rgba(0,0,0,0.02); padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; position: relative;">
                <button type="button" onclick="removeIns(1)" style="position: absolute; right: 15px; top: 15px; background: none; border: none; color: #ef4444; cursor: pointer; font-size: 20px; line-height: 1; font-weight: bold;" title="Hapus Pemateri 1">&times;</button>
                <div style="font-weight: 600; margin-bottom: 12px; color: var(--primary);">Pemateri 1</div>
                <div class="f @error('instructor') has-error @enderror" style="margin-bottom: 12px;">
                    <label for="instructor">Nama</label>
                    <input class="input" id="instructor" name="instructor" value="{{ old('instructor', $program->instructor) }}">
                    @error('instructor')<div class="ferr">{{ $message }}</div>@enderror
                </div>
                <div class="f @error('instructor_phone') has-error @enderror" style="margin:0;">
                    <label for="instructor_phone">No. WA (Untuk link Sertifikat)</label>
                    <input class="input" id="instructor_phone" type="tel" name="instructor_phone" value="{{ old('instructor_phone', $program->instructor_phone) }}">
                    @error('instructor_phone')<div class="ferr">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Pemateri 2 -->
            <div id="block-ins-2" class="f full" style="display: {{ $showIns2 ? 'block' : 'none' }}; background: rgba(0,0,0,0.02); padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; position: relative;">
                <button type="button" onclick="removeIns(2)" style="position: absolute; right: 15px; top: 15px; background: none; border: none; color: #ef4444; cursor: pointer; font-size: 20px; line-height: 1; font-weight: bold;" title="Hapus Pemateri 2">&times;</button>
                <div style="font-weight: 600; margin-bottom: 12px; color: var(--primary);">Pemateri 2</div>
                <div class="f @error('instructor_2') has-error @enderror" style="margin-bottom: 12px;">
                    <label for="instructor_2">Nama</label>
                    <input class="input" id="instructor_2" name="instructor_2" value="{{ old('instructor_2', $program->instructor_2) }}">
                    @error('instructor_2')<div class="ferr">{{ $message }}</div>@enderror
                </div>
                <div class="f @error('instructor_2_phone') has-error @enderror" style="margin:0;">
                    <label for="instructor_2_phone">No. WA (Untuk link Sertifikat)</label>
                    <input class="input" id="instructor_2_phone" type="tel" name="instructor_2_phone" value="{{ old('instructor_2_phone', $program->instructor_2_phone) }}">
                    @error('instructor_2_phone')<div class="ferr">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Pemateri 3 -->
            <div id="block-ins-3" class="f full" style="display: {{ $showIns3 ? 'block' : 'none' }}; background: rgba(0,0,0,0.02); padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; position: relative;">
                <button type="button" onclick="removeIns(3)" style="position: absolute; right: 15px; top: 15px; background: none; border: none; color: #ef4444; cursor: pointer; font-size: 20px; line-height: 1; font-weight: bold;" title="Hapus Pemateri 3">&times;</button>
                <div style="font-weight: 600; margin-bottom: 12px; color: var(--primary);">Pemateri 3</div>
                <div class="f @error('instructor_3') has-error @enderror" style="margin-bottom: 12px;">
                    <label for="instructor_3">Nama</label>
                    <input class="input" id="instructor_3" name="instructor_3" value="{{ old('instructor_3', $program->instructor_3) }}">
                    @error('instructor_3')<div class="ferr">{{ $message }}</div>@enderror
                </div>
                <div class="f @error('instructor_3_phone') has-error @enderror" style="margin:0;">
                    <label for="instructor_3_phone">No. WA (Untuk link Sertifikat)</label>
                    <input class="input" id="instructor_3_phone" type="tel" name="instructor_3_phone" value="{{ old('instructor_3_phone', $program->instructor_3_phone) }}">
                    @error('instructor_3_phone')<div class="ferr">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Moderator 1 -->
            <div id="block-mod-1" class="f full" style="display: {{ $showMod1 ? 'block' : 'none' }}; background: rgba(0,0,0,0.02); padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; position: relative;">
                <button type="button" onclick="removeMod(1)" style="position: absolute; right: 15px; top: 15px; background: none; border: none; color: #ef4444; cursor: pointer; font-size: 20px; line-height: 1; font-weight: bold;" title="Hapus Moderator 1">&times;</button>
                <div style="font-weight: 600; margin-bottom: 12px; color: var(--primary);">Moderator 1</div>
                <div class="f @error('moderator') has-error @enderror" style="margin-bottom: 12px;">
                    <label for="moderator">Nama</label>
                    <input class="input" id="moderator" name="moderator" value="{{ old('moderator', $program->moderator) }}">
                    @error('moderator')<div class="ferr">{{ $message }}</div>@enderror
                </div>
                <div class="f @error('moderator_phone') has-error @enderror" style="margin:0;">
                    <label for="moderator_phone">No. WA (Untuk link Sertifikat)</label>
                    <input class="input" id="moderator_phone" type="tel" name="moderator_phone" value="{{ old('moderator_phone', $program->moderator_phone) }}">
                    @error('moderator_phone')<div class="ferr">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Moderator 2 -->
            <div id="block-mod-2" class="f full" style="display: {{ $showMod2 ? 'block' : 'none' }}; background: rgba(0,0,0,0.02); padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; position: relative;">
                <button type="button" onclick="removeMod(2)" style="position: absolute; right: 15px; top: 15px; background: none; border: none; color: #ef4444; cursor: pointer; font-size: 20px; line-height: 1; font-weight: bold;" title="Hapus Moderator 2">&times;</button>
                <div style="font-weight: 600; margin-bottom: 12px; color: var(--primary);">Moderator 2</div>
                <div class="f @error('moderator_2') has-error @enderror" style="margin-bottom: 12px;">
                    <label for="moderator_2">Nama</label>
                    <input class="input" id="moderator_2" name="moderator_2" value="{{ old('moderator_2', $program->moderator_2) }}">
                    @error('moderator_2')<div class="ferr">{{ $message }}</div>@enderror
                </div>
                <div class="f @error('moderator_2_phone') has-error @enderror" style="margin:0;">
                    <label for="moderator_2_phone">No. WA (Untuk link Sertifikat)</label>
                    <input class="input" id="moderator_2_phone" type="tel" name="moderator_2_phone" value="{{ old('moderator_2_phone', $program->moderator_2_phone) }}">
                    @error('moderator_2_phone')<div class="ferr">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Moderator 3 -->
            <div id="block-mod-3" class="f full" style="display: {{ $showMod3 ? 'block' : 'none' }}; background: rgba(0,0,0,0.02); padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; position: relative;">
                <button type="button" onclick="removeMod(3)" style="position: absolute; right: 15px; top: 15px; background: none; border: none; color: #ef4444; cursor: pointer; font-size: 20px; line-height: 1; font-weight: bold;" title="Hapus Moderator 3">&times;</button>
                <div style="font-weight: 600; margin-bottom: 12px; color: var(--primary);">Moderator 3</div>
                <div class="f @error('moderator_3') has-error @enderror" style="margin-bottom: 12px;">
                    <label for="moderator_3">Nama</label>
                    <input class="input" id="moderator_3" name="moderator_3" value="{{ old('moderator_3', $program->moderator_3) }}">
                    @error('moderator_3')<div class="ferr">{{ $message }}</div>@enderror
                </div>
                <div class="f @error('moderator_3_phone') has-error @enderror" style="margin:0;">
                    <label for="moderator_3_phone">No. WA (Untuk link Sertifikat)</label>
                    <input class="input" id="moderator_3_phone" type="tel" name="moderator_3_phone" value="{{ old('moderator_3_phone', $program->moderator_3_phone) }}">
                    @error('moderator_3_phone')<div class="ferr">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="f full @error('whatsapp_group_link') has-error @enderror" style="margin-top: 20px;">
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
            
            <!-- SECTION: SERTIFIKAT PESERTA -->
            @foreach(['certificate_front' => 'Depan', 'certificate_back' => 'Belakang'] as $field => $side)
                <div class="f @error($field) has-error @enderror">
                    <label for="{{ $field }}">Template Sertifikat PESERTA {{ $side }} (PNG) @unless(filled($program->{$field}))<span class="req">*</span>@endunless</label>
                    <input class="input" id="{{ $field }}" type="file" name="{{ $field }}" accept="image/png" @required(! filled($program->{$field}))>
                    <div class="hint">Min. 1000 x 700 px, maks. 3600 x 2600 px, 8 MB.</div>
                    @if(filled($program->{$field}))
                        <div class="muted small">Template sudah diunggah.</div>
                    @endif
                    @error($field)<div class="ferr">{{ $message }}</div>@enderror
                </div>
            @endforeach

            <!-- SECTION: SERTIFIKAT PEMATERI -->
            @foreach(['instructor_certificate_front' => 'Depan', 'instructor_certificate_back' => 'Belakang'] as $field => $side)
                <div class="f @error($field) has-error @enderror">
                    <label for="{{ $field }}">Template Sertifikat PEMATERI {{ $side }} (PNG)</label>
                    <input class="input" id="{{ $field }}" type="file" name="{{ $field }}" accept="image/png">
                    <div class="hint">Kosongkan jika ingin memakai desain sertifikat peserta.</div>
                    @if(filled($program->{$field}))
                        <div class="muted small">Template sudah diunggah.</div>
                    @endif
                    @error($field)<div class="ferr">{{ $message }}</div>@enderror
                </div>
            @endforeach

            <!-- SECTION: SERTIFIKAT MODERATOR -->
            @foreach(['moderator_certificate_front' => 'Depan', 'moderator_certificate_back' => 'Belakang'] as $field => $side)
                <div class="f @error($field) has-error @enderror">
                    <label for="{{ $field }}">Template Sertifikat MODERATOR {{ $side }} (PNG)</label>
                    <input class="input" id="{{ $field }}" type="file" name="{{ $field }}" accept="image/png">
                    <div class="hint">Kosongkan jika ingin memakai desain sertifikat peserta.</div>
                    @if(filled($program->{$field}))
                        <div class="muted small">Template sudah diunggah.</div>
                    @endif
                    @error($field)<div class="ferr">{{ $message }}</div>@enderror
                </div>
            @endforeach

            <div class="f">
                <label class="chk"><input type="checkbox" name="is_active" value="1" @checked($isActive)> Aktif (Tampil di halaman publik)</label>
            </div>
            
            <div class="f">
                <label class="chk" style="color:#4f46e5; font-weight:600;"><input type="checkbox" name="is_closed" value="1" @checked($isClosed)> Tutup Pendaftaran (Berfungsi sebagai Portofolio)</label>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $editing ? 'Simpan Perubahan' : 'Simpan Kelas' }}</button>
            <a class="btn ghost" href="{{ route('admin.kelas.index') }}">Batal</a>
        </div>
    </form>
</div>

<!-- SCRIPT UNTUK FORM DINAMIS PENGAJAR & MODERATOR -->
<script>
    function updateBtns() {
        const ins1 = document.getElementById('block-ins-1').style.display !== 'none';
        const ins2 = document.getElementById('block-ins-2').style.display !== 'none';
        const ins3 = document.getElementById('block-ins-3').style.display !== 'none';
        
        const mod1 = document.getElementById('block-mod-1').style.display !== 'none';
        const mod2 = document.getElementById('block-mod-2').style.display !== 'none';
        const mod3 = document.getElementById('block-mod-3').style.display !== 'none';
        
        document.getElementById('btn-add-ins').style.display = (ins1 && ins2 && ins3) ? 'none' : 'inline-block';
        document.getElementById('btn-add-mod').style.display = (mod1 && mod2 && mod3) ? 'none' : 'inline-block';
    }

    function addIns() {
        if (document.getElementById('block-ins-1').style.display === 'none') {
            document.getElementById('block-ins-1').style.display = 'block';
        } else if (document.getElementById('block-ins-2').style.display === 'none') {
            document.getElementById('block-ins-2').style.display = 'block';
        } else if (document.getElementById('block-ins-3').style.display === 'none') {
            document.getElementById('block-ins-3').style.display = 'block';
        }
        updateBtns();
    }

    function addMod() {
        if (document.getElementById('block-mod-1').style.display === 'none') {
            document.getElementById('block-mod-1').style.display = 'block';
        } else if (document.getElementById('block-mod-2').style.display === 'none') {
            document.getElementById('block-mod-2').style.display = 'block';
        } else if (document.getElementById('block-mod-3').style.display === 'none') {
            document.getElementById('block-mod-3').style.display = 'block';
        }
        updateBtns();
    }

    function removeIns(num) {
        document.getElementById('block-ins-' + num).style.display = 'none';
        
        if(num === 1) { 
            document.getElementById('instructor').value = ''; 
            document.getElementById('instructor_phone').value = ''; 
        } else if(num === 2) { 
            document.getElementById('instructor_2').value = ''; 
            document.getElementById('instructor_2_phone').value = ''; 
        } else if(num === 3) { 
            document.getElementById('instructor_3').value = ''; 
            document.getElementById('instructor_3_phone').value = ''; 
        }
        updateBtns();
    }

    function removeMod(num) {
        document.getElementById('block-mod-' + num).style.display = 'none';
        
        if(num === 1) {
            document.getElementById('moderator').value = '';
            document.getElementById('moderator_phone').value = '';
        } else if(num === 2) {
            document.getElementById('moderator_2').value = '';
            document.getElementById('moderator_2_phone').value = '';
        } else if(num === 3) {
            document.getElementById('moderator_3').value = '';
            document.getElementById('moderator_3_phone').value = '';
        }
        updateBtns();
    }

    document.addEventListener("DOMContentLoaded", function() {
        updateBtns();
    });
</script>
@endsection