<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateController extends Controller
{
    public function show(Request $request, string $number)
    {
        // 1. CEK APAKAH INI SERTIFIKAT PEMATERI
        $programAsInstructor = Program::whereRaw("CONCAT(certificate_prefix, '-', instructor_phone) = ?", [$number])->first();

        if ($programAsInstructor) {
            return $this->renderCertificate($request, [
                'name' => $programAsInstructor->instructor, // Mengambil nama pemateri
                'number' => $number,
                'date' => now(), 
                'url' => url('/sertifikat/' . $number),
                // Gunakan template pemateri. Jika kosong, otomatis pakai template peserta
                'front' => $programAsInstructor->instructor_certificate_front ?: $programAsInstructor->certificate_front,
                'back' => $programAsInstructor->instructor_certificate_back ?: $programAsInstructor->certificate_back,
            ]);
        }

        // 2. JIKA BUKAN PEMATERI, CEK APAKAH INI SERTIFIKAT PESERTA
        $registration = Registration::with('program')->where('certificate_number', $number)->first();

        if (! $registration || ! $registration->isCertificateIssued()) {
            return view('certificate.not-found', ['number' => $number]);
        }

        $program = $registration->program;

        if (! $program->hasCertificateTemplate()) {
            abort(404, 'Template sertifikat belum lengkap. Hubungi admin.');
        }

        return $this->renderCertificate($request, [
            'name' => $registration->name,
            'number' => $registration->certificate_number,
            'date' => $registration->certificate_issued_at ?? now(),
            'url' => $registration->certificateQrUrl(),
            'front' => $program->certificate_front,
            'back' => $program->certificate_back,
        ]);
    }

    // Fungsi helper untuk merender PDF via Base64
    private function renderCertificate(Request $request, array $data)
    {
        $qrcode = base64_encode(QrCode::format('svg')->size(120)->generate($data['url']));

        $frontBase64 = '';
        $backBase64 = '';

        if ($data['front'] && Storage::disk('local')->exists($data['front'])) {
            $frontContent = Storage::disk('local')->get($data['front']);
            $frontBase64 = 'data:image/png;base64,' . base64_encode($frontContent);
        }

        if ($data['back'] && Storage::disk('local')->exists($data['back'])) {
            $backContent = Storage::disk('local')->get($data['back']);
            $backBase64 = 'data:image/png;base64,' . base64_encode($backContent);
        }

        $pdf = Pdf::loadView('certificate.pdf', [
            'name' => $data['name'],
            'number' => $data['number'],
            'date' => $data['date'],
            'qrcode' => $qrcode,
            'frontTemplate' => $frontBase64,
            'backTemplate' => $backBase64,
        ]);

        $pdf->setPaper('A4', 'landscape');

        if ($request->query('unduh')) {
            return $pdf->download('Sertifikat-' . $data['number'] . '.pdf');
        }

        return $pdf->stream('Sertifikat-' . $data['number'] . '.pdf');
    }
}