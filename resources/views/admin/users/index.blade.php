@extends('admin.layout')
@section('title', 'Kelola Pengguna')
@section('heading', 'Kelola Pengguna')
@section('content')
<div class="toolbar">
    <form method="GET">
        <input class="input" type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email...">
        <button class="btn ghost" type="submit">Cari</button>
    </form>
    <a class="btn" href="{{ route('admin.pengguna.create') }}">+ Tambah Pengguna</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th><th>Dibuat</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($users as $u)
                <tr>
                    <td><strong>{{ $u->name }}</strong>@if($u->id === auth()->id()) <span class="badge b-info">Anda</span>@endif</td>
                    <td>{{ $u->email }}</td>
                    <td><span class="badge {{ $u->role === 'admin' ? 'b-info' : 'b-gray' }}">{{ \App\Models\User::ROLES[$u->role] ?? $u->role }}</span></td>
                    <td><span class="badge {{ $u->is_active ? 'b-ok' : 'b-off' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="small">{{ $u->created_at?->format('d M Y') }}</td>
                    <td>
                        <div class="acts">
                            <a class="btn ghost sm" href="{{ route('admin.pengguna.edit', $u->id) }}">Edit</a>
                            @if($u->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.pengguna.destroy', $u->id) }}" onsubmit="return confirm('Hapus pengguna ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn danger sm" type="submit">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Tidak ada pengguna.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</div>
@endsection
