@extends('admin.layout')
@php
    $editing = $user->exists;
    $self = $editing && $user->id === auth()->id();
    $hasOld = count(old()) > 0;
    $isActive = $hasOld ? (bool) old('is_active') : (bool) ($user->is_active ?? true);
@endphp
@section('title', $editing ? 'Edit Pengguna' : 'Tambah Pengguna')
@section('heading', $editing ? 'Edit Pengguna' : 'Tambah Pengguna')
@section('content')
<div class="card" style="max-width:760px">
    <form method="POST" action="{{ $editing ? route('admin.pengguna.update', $user->id) : route('admin.pengguna.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="fgrid">
            <div class="f @error('name') has-error @enderror">
                <label for="name">Nama <span class="req">*</span></label>
                <input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="ferr">{{ $message }}</div>@enderror
            </div>
            <div class="f @error('email') has-error @enderror">
                <label for="email">Email <span class="req">*</span></label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="ferr">{{ $message }}</div>@enderror
            </div>
            <div class="f @error('password') has-error @enderror">
                <label for="password">Kata Sandi @unless($editing)<span class="req">*</span>@endunless</label>
                <input class="input" id="password" type="password" name="password" autocomplete="new-password" @unless($editing) required @endunless>
                <div class="hint">{{ $editing ? 'Kosongkan jika tidak ingin mengubah. ' : '' }}Minimal 8 karakter.</div>
                @error('password')<div class="ferr">{{ $message }}</div>@enderror
            </div>
            <div class="f">
                <label for="password_confirmation">Ulangi Kata Sandi</label>
                <input class="input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
            </div>
            <div class="f @error('role') has-error @enderror">
                <label for="role">Peran <span class="req">*</span></label>
                <select class="input" id="role" name="role" @disabled($self)>
                    @foreach(\App\Models\User::ROLES as $k => $v)
                        <option value="{{ $k }}" @selected(old('role', $user->role ?? 'staff') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                @if($self)<input type="hidden" name="role" value="{{ $user->role }}">@endif
                <div class="hint">Admin: akses penuh termasuk kelola pengguna. Staf: kelola kelas &amp; pendaftar.</div>
                @error('role')<div class="ferr">{{ $message }}</div>@enderror
            </div>
            <div class="f" style="display:flex;align-items:center">
                <label class="chk"><input type="checkbox" name="is_active" value="1" @checked($isActive) @disabled($self)> Akun aktif</label>
                @if($self)<input type="hidden" name="is_active" value="1">@endif
            </div>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $editing ? 'Simpan Perubahan' : 'Simpan Pengguna' }}</button>
            <a class="btn ghost" href="{{ route('admin.pengguna.index') }}">Batal</a>
        </div>
    </form>
</div>
@endsection
