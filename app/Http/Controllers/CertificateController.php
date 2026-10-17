<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateController extends Controller
{
    public function show(Request $request, string $number)
    {
        // Cari pendaftaran beserta relasi program
        $registration = Registration::with('program')->where('certificate_number', $number)->first();

        // Jika tidak ditemukan atau status bukan 'certificate_issued'
        if (! $registration || ! $registration->isCertificateIssued()) {
            return view('certificate.not-found', ['number' => $number]);
        }

        $program = $registration->program;

        // Pastikan template sudah diupload admin
        if (! $program->hasCertificateTemplate()) {
            abort(404, 'Template sertifikat belum lengkap. Hubungi admin.');
        }

        // Poin 9: Buat QR Code (berisi URL akses sertifikat) dalam format SVG Base64
        $url = $registration->certificateQrUrl(); 
        $qrcode = base64_encode(QrCode::format('svg')->size(120)->generate($url));

        // Poin 6: Load view untuk dicetak jadi PDF
        $pdf = Pdf::loadView('certificate.pdf', [
            'registration' => $registration,
            'program' => $program,
            'qrcode' => $qrcode,
        ]);

        // Setting kertas A4 Landscape
        $pdf->setPaper('A4', 'landscape');

        // Jika ada query ?unduh=1 (dari tombol di Kelas Saya), otomatis unduh file
        if ($request->query('unduh')) {
            return $pdf->download('Sertifikat-' . $registration->certificate_number . '.pdf');
        }

        // Tampilkan di browser
        return $pdf->stream('Sertifikat-' . $registration->certificate_number . '.pdf');
    }
}