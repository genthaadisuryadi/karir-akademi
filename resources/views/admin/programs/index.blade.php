@extends('admin.layout')
@section('title', 'Kelola Kelas')
@section('heading', 'Kelola Kelas')
@section('content')
<div class="toolbar">
    <form method="GET">
        <input class="input" type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul kelas...">
        <button class="btn ghost" type="submit">Cari</button>
    </form>
    <a class="btn" href="{{ route('admin.kelas.create') }}">+ Tambah Kelas</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Poster</th><th>Kelas</th><th>Jadwal</th><th>JP</th><th>Pendaftar</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($programs as $p)
                <tr style="{{ !$p->is_active ? 'opacity: 0.75; background-color: rgba(0,0,0,0.015);' : '' }}">
                    <td>@if($p->poster)<img class="thumb" src="{{ asset($p->poster) }}" alt="" style="{{ !$p->is_active ? 'filter: grayscale(100%);' : '' }}">@else<div class="thumb"></div>@endif</td>
                    <td style="min-width:240px">
                        <strong>{{ $p->title }}</strong>
                        <div class="small muted">{{ $p->audienceLabel() }} &middot; /pelatihan/{{ $p->slug }}/daftar</div>
                        <div class="small muted">Prefix: <code>{{ $p->certificate_prefix ?: '-' }}</code> &middot; Template: {{ $p->hasCertificateTemplate() ? 'lengkap' : 'belum diunggah' }}</div>
                    </td>
                    <td class="small">{{ $p->date_label }}@if($p->time_label)<br>{{ $p->time_label }}@endif</td>
                    <td>{{ $p->jp }}</td>
                    <td>{{ $p->registrations_count }}@if($p->quota) <span class="muted small">/ {{ $p->quota }}</span>@endif</td>
                    <td>
                        <!-- Label Status -->
                        @if($p->is_active)
                            <span class="badge b-ok" style="background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid #10b981;">Aktif</span>
                        @else
                            <span class="badge b-gray" style="background: rgba(107, 114, 128, 0.1); color: #4b5563; border: 1px solid #9ca3af;">Diarsipkan</span>
                        @endif
                    </td>
                    <td>
                        <div class="acts" style="display: flex; gap: 4px; flex-wrap: wrap; justify-content: flex-start;">
                            <a class="btn ghost sm" href="{{ route('admin.kelas.edit', $p->id) }}">Edit</a>
                            
                            <!-- Tombol Arsipkan/Aktifkan -->
                            <form method="POST" action="{{ route('admin.kelas.toggle-status', $p->id) }}" style="margin:0">
                                @csrf @method('PATCH')
                                @if($p->is_active)
                                    <button class="btn sm yellow" type="submit" onclick="return confirm('Arsipkan kelas ini? Kelas tidak akan terlihat lagi di halaman publik.')" style="background-color: #f59e0b; color: white;">Arsipkan</button>
                                @else
                                    <button class="btn sm" type="submit" onclick="return confirm('Aktifkan kembali kelas ini? Kelas akan langsung tayang di halaman publik.')" style="background-color: #10b981; color: white;">Aktifkan</button>
                                @endif
                            </form>

                            <form method="POST" action="{{ route('admin.kelas.destroy', $p->id) }}" onsubmit="return confirm('Hapus kelas ini? Tindakan tidak dapat dibatalkan.')" style="margin:0">
                                @csrf @method('DELETE')
                                <button class="btn danger sm" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Belum ada kelas. Klik "Tambah Kelas" untuk membuat yang pertama.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $programs->links() }}
</div>
@endsection