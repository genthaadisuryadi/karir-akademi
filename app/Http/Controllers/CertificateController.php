<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateController extends Controller
{
    public function show(Request $request, string $number)
    {
        $registration = Registration::with('program')->where('certificate_number', $number)->first();

        if (! $registration || ! $registration->isCertificateIssued()) {
            return view('certificate.not-found', ['number' => $number]);
        }

        $program = $registration->program;

        if (! $program->hasCertificateTemplate()) {
            abort(404, 'Template sertifikat belum lengkap. Hubungi admin.');
        }

        $url = $registration->certificateQrUrl(); 
        $qrcode = base64_encode(QrCode::format('svg')->size(120)->generate($url));

        // METODE BARU: Konversi template ke Base64 agar DOMPDF tidak perlu membaca file path lagi
        $frontBase64 = '';
        $backBase64 = '';

        if (Storage::disk('local')->exists($program->certificate_front)) {
            $frontContent = Storage::disk('local')->get($program->certificate_front);
            $frontBase64 = 'data:image/png;base64,' . base64_encode($frontContent);
        }

        if (Storage::disk('local')->exists($program->certificate_back)) {
            $backContent = Storage::disk('local')->get($program->certificate_back);
            $backBase64 = 'data:image/png;base64,' . base64_encode($backContent);
        }

        $pdf = Pdf::loadView('certificate.pdf', [
            'registration' => $registration,
            'program' => $program,
            'qrcode' => $qrcode,
            'frontTemplate' => $frontBase64,
            'backTemplate' => $backBase64,
        ]);

        $pdf->setPaper('A4', 'landscape');

        if ($request->query('unduh')) {
            return $pdf->download('Sertifikat-' . $registration->certificate_number . '.pdf');
        }

        return $pdf->stream('Sertifikat-' . $registration->certificate_number . '.pdf');
    }
}