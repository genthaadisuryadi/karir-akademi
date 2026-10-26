<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Registration;
use App\Support\Export\PdfTableWriter;
use App\Support\Export\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Kelola data pendaftar kelas. */
class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $registrations = $this->query($request)->paginate(15)->withQueryString();
        $programs = Program::orderBy('sort_order')->get(['id', 'short_title']);

        return view('admin.registrations.index', compact('registrations', 'programs'));
    }

    public function show(int $id)
    {
        $registration = Registration::with('program')->findOrFail($id);

        return view('admin.registrations.show', compact('registration'));
    }

    /** Ubah status peserta: Mendaftar | Terbit sertifikat | Tidak terbit sertifikat. */
    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Registration::STATUSES))],
        ]);

        $registration = Registration::with('program')->findOrFail($id);

        if ($data['status'] === 'certificate_issued') {
            if (! $registration->program->hasCertificateTemplate()) {
                return back()->with('error', 'Template sertifikat kelas ini belum diunggah. Unggah template di Kelola Kelas terlebih dahulu.');
            }

            // Tanggal terbit dicatat sekali; mengubah status bolak-balik tidak mengubah tanggal pertama.
            $data['certificate_issued_at'] = $registration->certificate_issued_at ?? now();

            // Pendaftar lama yang nomornya bentrok/kosong dilengkapi di sini.
            if (blank($registration->certificate_number)) {
                $number = Registration::makeCertificateNumber($registration->program, $registration->phone);

                if (Registration::where('certificate_number', $number)->exists()) {
                    return back()->with('error', "Nomor sertifikat {$number} sudah dipakai pendaftar lain (nomor WhatsApp sama pada kelas ini). Periksa data pendaftar ganda terlebih dahulu.");
                }

                $data['certificate_number'] = $number;
            }
        }

        $registration->update($data);

        return back()->with('success', 'Status pendaftar diperbarui.');
    }

    /** FITUR BARU: Mengubah status banyak pendaftar sekaligus (Bulk Action) */
    public function bulkUpdateStatus(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:registrations,id'],
            'status' => ['required', Rule::in(array_keys(Registration::STATUSES))],
        ]);

        $status = $data['status'];
        $registrations = Registration::with('program')->whereIn('id', $data['ids'])->get();

        $successCount = 0;
        $errorMessages = [];

        foreach ($registrations as $registration) {
            $updateData = ['status' => $status];

            if ($status === 'certificate_issued') {
                if (! $registration->program->hasCertificateTemplate()) {
                    $errorMessages[] = "Template sertifikat kelas '{$registration->program->short_title}' belum diunggah.";
                    continue; // Lewati peserta ini jika template kelasnya belum ada
                }

                $updateData['certificate_issued_at'] = $registration->certificate_issued_at ?? now();

                if (blank($registration->certificate_number)) {
                    $number = Registration::makeCertificateNumber($registration->program, $registration->phone);

                    if (Registration::where('certificate_number', $number)->exists()) {
                        $errorMessages[] = "Nomor sertifikat {$number} sudah terpakai.";
                        continue; // Lewati jika nomor WA ganda
                    }

                    $updateData['certificate_number'] = $number;
                }
            }

            $registration->update($updateData);
            $successCount++;
        }

        if (count($errorMessages) > 0) {
            $errorString = implode(' ', array_unique($errorMessages));
            return back()->with('warning', "Berhasil memproses $successCount data. Namun ada yang gagal: $errorString");
        }

        return back()->with('success', "Status $successCount pendaftar berhasil diperbarui secara massal.");
    }

    public function destroy(int $id)
    {
        Registration::findOrFail($id)->delete();

        return redirect()->route('admin.pendaftar.index')->with('success', 'Data pendaftar dihapus.');
    }

    /** Ekspor data pendaftar sesuai filter yang aktif. Format: csv (default), xlsx, pdf. */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'csv'));
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        $filename = 'pendaftar-'.now()->format('Ymd-His').'.'.$format;

        return match ($format) {
            'xlsx' => $this->exportXlsx($request, $filename),
            'pdf' => $this->exportPdf($request, $filename),
            default => $this->exportCsv($request, $filename),
        };
    }

    private function query(Request $request)
    {
        return Registration::with('program')
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->program_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('school_name', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('certificate_number', 'like', $term));
            })
            ->latest();
    }

    private const EXPORT_HEADERS = [
        'Kode', 'Program', 'Nama', 'Email', 'WhatsApp', 'Asal Sekolah/Instansi', 'Kelas', 'Kota/Kab',
        'Jabatan/Mapel', 'Motivasi', 'Status', 'No. Sertifikat', 'Tanggal Daftar',
    ];

    /** @return list<string|int|null> */
    private function exportRow(Registration $r): array
    {
        return [
            $r->code, $r->program->title, $r->name, $r->email, $r->phone, $r->school_name,
            $r->class_name, $r->city, $r->position, $r->motivation,
            Registration::STATUSES[$r->status] ?? $r->status,
            $r->certificate_number,
            $r->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function exportCsv(Request $request, string $filename)
    {
        $query = $this->query($request);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

            $this->csvRow($out, self::EXPORT_HEADERS);

            foreach ($query->cursor() as $r) {
                $this->csvRow($out, $this->exportRow($r));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportXlsx(Request $request, string $filename)
    {
        $query = $this->query($request);

        $rows = (function () use ($query) {
            foreach ($query->cursor() as $r) {
                yield $this->exportRow($r);
            }
        })();

        $content = XlsxWriter::build(
            'Pendaftar',
            self::EXPORT_HEADERS,
            $rows,
            [14, 36, 24, 28, 16, 30, 9, 18, 18, 40, 20, 34, 17],
        );

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function exportPdf(Request $request, string $filename)
    {
        $query = $this->query($request);

        $filters = [];
        if ($request->filled('program_id') && ($p = Program::find($request->program_id))) {
            $filters[] = 'Kelas: '.$p->short_title;
        }
        if ($request->filled('status')) {
            $filters[] = 'Status: '.(Registration::STATUSES[$request->status] ?? $request->status);
        }
        if ($request->filled('q')) {
            $filters[] = 'Pencarian: '.$request->q;
        }

        $pdf = new PdfTableWriter(
            'Data Pendaftar',
            'Diekspor '.now()->format('d M Y').' | Total '.(clone $query)->count().' pendaftar'
                .($filters ? ' | '.implode(', ', $filters) : ''),
            [
                ['Kode', 8], ['Kelas', 16], ['Nama', 12], ['Email / WhatsApp', 17],
                ['Sekolah/Instansi', 22], ['Status', 10], ['Tanggal Daftar', 9],
            ],
        );

        foreach ($query->cursor() as $r) {
            $pdf->addRow([
                $r->code,
                $r->program->short_title,
                $r->name,
                $r->email."\n".$r->phone,
                ($r->school_name ?: '-')."\n".trim(($r->class_name ? 'Kelas '.$r->class_name.' - ' : '').$r->city, ' -'),
                Registration::STATUSES[$r->status] ?? $r->status,
                $r->created_at?->format('d M Y H:i'),
            ]);
        }

        $content = $pdf->output();

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    private function csvRow($handle, array $row): void
    {
        // Cegah CSV/formula injection di Excel.
        $row = array_map(function ($v) {
            $v = (string) $v;

            return $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) ? "'".$v : $v;
        }, $row);

        fputcsv($handle, $row, ',', '"', '\\');
    }
}