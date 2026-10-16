@extends('admin.layout')
@section('title', 'Pendaftar')
@section('heading', 'Data Pendaftar')
@section('content')
<div class="toolbar">
    <form method="GET">
        <input class="input" type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama/email/sekolah/kode/no. sertifikat">
        <select class="input" name="program_id">
            <option value="">Semua kelas</option>
            @foreach($programs as $p)
                <option value="{{ $p->id }}" @selected(request('program_id') == $p->id)>{{ \Illuminate\Support\Str::limit($p->short_title, 40) }}</option>
            @endforeach
        </select>
        <select class="input" name="status">
            <option value="">Semua status</option>
            @foreach(\App\Models\Registration::STATUSES as $k => $v)
                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
            @endforeach
        </select>
        <button class="btn ghost" type="submit">Filter</button>
        @if(request()->hasAny(['q','program_id','status']))<a class="btn ghost" href="{{ route('admin.pendaftar.index') }}">Reset</a>@endif
    </form>
    
    <!-- Tombol Export Menggunakan Ikon -->
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="btn yellow" href="{{ route('admin.pendaftar.export', request()->query() + ['format' => 'pdf']) }}" title="Ekspor PDF" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <path d="M12 18v-6"></path><path d="M9 15h6"></path>
            </svg>
            PDF
        </a>
        
        <a class="btn yellow" href="{{ route('admin.pendaftar.export', request()->query() + ['format' => 'csv']) }}" title="Ekspor CSV" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            CSV
        </a>
        
        <a class="btn yellow" href="{{ route('admin.pendaftar.export', request()->query() + ['format' => 'xlsx']) }}" title="Ekspor Excel" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <path d="M8 13h2"></path><path d="M8 17h2"></path>
                <path d="M14 13h2"></path><path d="M14 17h2"></path>
            </svg>
            Excel
        </a>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Peserta</th><th>Sekolah/Instansi</th><th>Kelas</th><th>Tanggal</th><th>Status</th><th>Sertifikat</th><th></th></tr></thead>
            <tbody>
            @forelse($registrations as $r)
                <tr>
                    <td><strong>{{ $r->code }}</strong></td>
                    <td>{{ $r->name }}<div class="muted small">{{ $r->email }} &middot; {{ $r->phone }}</div></td>
                    <td class="small">{{ $r->school_name ?: '-' }}<div class="muted">{{ $r->class_name ? 'Kelas '.$r->class_name.' · ' : '' }}{{ $r->city }}</div></td>
                    <td class="small">{{ \Illuminate\Support\Str::limit($r->program->short_title, 34) }}</td>
                    <td class="small">{{ $r->created_at->format('d M Y H:i') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.pendaftar.status', $r->id) }}" style="margin:0">
                            @csrf @method('PATCH')
                            <select class="input" name="status" aria-label="Status {{ $r->code }}" style="width:auto;min-width:190px;padding:6px 10px;font-size:13px" onchange="this.form.submit()">
                                @foreach(\App\Models\Registration::STATUSES as $k => $v)
                                    <option value="{{ $k }}" @selected($r->status === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td class="small">
                        @if($r->isCertificateIssued() && $r->certificateUrl())
                            <a href="{{ $r->certificateUrl() }}" target="_blank" rel="noopener" style="color:var(--primary);font-weight:600">Lihat PDF</a>
                        @else
                            <span class="muted">-</span>
                        @endif
                        @if($r->certificate_number)<div class="muted" style="word-break:break-all">{{ $r->certificate_number }}</div>@endif
                    </td>
                    <td><a class="btn ghost sm" href="{{ route('admin.pendaftar.show', $r->id) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">Tidak ada data pendaftar.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $registrations->links() }}
</div>
@endsection