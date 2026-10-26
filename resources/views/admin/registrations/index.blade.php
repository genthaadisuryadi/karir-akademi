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
    
    <!-- Tombol Export -->
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
    <!-- Panel Aksi Massal (Bulk Action) -->
    <div style="padding: 12px 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; background-color: rgba(0,0,0,0.02);">
        <form id="bulk-form" method="POST" action="{{ route('admin.pendaftar.bulk-status') }}" style="display:flex; gap:10px; align-items:center; margin:0;">
            @csrf
            <span style="font-size: 14px; font-weight: 600; color: #374151;">Dengan data terpilih:</span>
            <select name="status" class="input" style="width:220px; padding: 6px 10px;" required>
                <option value="">-- Ubah Status Massal --</option>
                @foreach(\App\Models\Registration::STATUSES as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
            <button type="button" class="btn" style="background-color: var(--primary); color: white;" onclick="submitBulkForm()">Terapkan</button>
            <div id="bulk-hidden-inputs"></div>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" id="check-all" title="Pilih Semua"></th>
                    <th>Kode</th>
                    <th>Peserta</th>
                    <th>Sekolah/Instansi</th>
                    <th>Kelas</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Sertifikat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($registrations as $r)
                <tr>
                    <td><input type="checkbox" class="bulk-cb" value="{{ $r->id }}"></td>
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
                <tr><td colspan="9" class="empty">Tidak ada data pendaftar.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $registrations->links() }}
</div>

<!-- Script untuk mengontrol Checkbox dan Bulk Action -->
<script>
    // Fitur Check/Uncheck Semua
    document.getElementById('check-all').addEventListener('change', function(e) {
        document.querySelectorAll('.bulk-cb').forEach(cb => cb.checked = e.target.checked);
    });

    // Fitur Submit Bulk Action
    function submitBulkForm() {
        const checked = document.querySelectorAll('.bulk-cb:checked');
        if (checked.length === 0) {
            alert('Pilih setidaknya satu pendaftar dengan mencentang kotak di sebelah kiri tabel.');
            return;
        }
        
        const form = document.getElementById('bulk-form');
        const select = form.querySelector('select[name="status"]');
        if (select.value === '') {
            alert('Silakan pilih status yang ingin diterapkan dari daftar menu tarik-turun (dropdown).');
            return;
        }

        const statusLabel = select.options[select.selectedIndex].text;
        if (!confirm(`Apakah Anda yakin ingin mengubah status ${checked.length} pendaftar menjadi "${statusLabel}"?`)) {
            return;
        }

        // Kumpulkan semua ID yang dicentang dan jadikan input tersembunyi
        const hiddenInputs = document.getElementById('bulk-hidden-inputs');
        hiddenInputs.innerHTML = ''; 
        
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            hiddenInputs.appendChild(input);
        });

        // Kirim form
        form.submit();
    }
</script>
@endsection