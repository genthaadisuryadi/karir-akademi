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
                <tr>
                    <td>@if($p->poster)<img class="thumb" src="{{ asset($p->poster) }}" alt="">@else<div class="thumb"></div>@endif</td>
                    <td style="min-width:240px">
                        <strong>{{ $p->title }}</strong>
                        <div class="small muted">{{ $p->audienceLabel() }} &middot; /pelatihan/{{ $p->slug }}/daftar</div>
                        <div class="small muted">Prefix: <code>{{ $p->certificate_prefix ?: '-' }}</code> &middot; Template: {{ $p->hasCertificateTemplate() ? 'lengkap' : 'belum diunggah' }}</div>
                    </td>
                    <td class="small">{{ $p->date_label }}@if($p->time_label)<br>{{ $p->time_label }}@endif</td>
                    <td>{{ $p->jp }}</td>
                    <td>{{ $p->registrations_count }}@if($p->quota) <span class="muted small">/ {{ $p->quota }}</span>@endif</td>
                    <td><span class="badge {{ $p->is_active ? 'b-ok' : 'b-gray' }}">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td>
                        <div class="acts">
                            <a class="btn ghost sm" href="{{ route('admin.kelas.edit', $p->id) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.kelas.destroy', $p->id) }}" onsubmit="return confirm('Hapus kelas ini? Tindakan tidak dapat dibatalkan.')">
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
