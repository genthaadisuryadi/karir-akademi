@extends('admin.layout')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('content')
<div class="stats">
    <div class="stat"><div class="n">{{ $stats['programs'] }}</div><div class="t">Total Kelas ({{ $stats['active_programs'] }} aktif)</div></div>
    <div class="stat"><div class="n">{{ $stats['registrations'] }}</div><div class="t">Total Pendaftar</div></div>
    <div class="stat y"><div class="n">{{ $stats['issued'] }}</div><div class="t">Sertifikat Terbit</div></div>
    <div class="stat"><div class="n">{{ $stats['users'] }}</div><div class="t">Pengguna Admin/Staf</div></div>
</div>

<div class="grid-2">
    <div class="card">
        <h2 class="ttl">Pendaftar Terbaru</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nama</th><th>Kelas</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($recent as $r)
                    <tr>
                        <td><strong>{{ $r->name }}</strong><div class="muted small">{{ $r->school_name ?: '-' }}</div></td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($r->program->short_title, 36) }}</td>
                        <td>
                            <span class="badge {{ ['registered'=>'b-wait','certificate_issued'=>'b-ok','certificate_not_issued'=>'b-off'][$r->status] ?? 'b-gray' }}">{{ \App\Models\Registration::STATUSES[$r->status] ?? $r->status }}</span>
                        </td>
                        <td><a class="btn ghost sm" href="{{ route('admin.pendaftar.show', $r->id) }}">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">Belum ada pendaftar.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2 class="ttl">Pendaftar per Kelas</h2>
        @php
            $max = max(1, (int) $programs->max('registrations_count'));
        @endphp
        @forelse($programs as $p)
            <div class="prow">
                <div class="l"><span>{{ \Illuminate\Support\Str::limit($p->short_title, 34) }}</span><strong>{{ $p->registrations_count }}</strong></div>
                <div class="bar"><i style="width: {{ ($p->registrations_count / $max) * 100 }}%"></i></div>
            </div>
        @empty
            <div class="empty">Belum ada kelas.</div>
        @endforelse
        <a class="btn yellow sm" href="{{ route('admin.kelas.create') }}">+ Tambah Kelas</a>
    </div>
</div>
@endsection
