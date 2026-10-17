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
        // Cari data pendaftaran beserta relasi program
        $registration = Registration::with('program')->where('certificate_number', $number)->first();

        // Jika tidak ditemukan ATAU statusnya bukan 'certificate_issued' (Terbit Sertifikat)
        if (! $registration || ! $registration->isCertificateIssued()) {
            return view('certificate.not-found', ['number' => $number]);
        }

        $program = $registration->program;

        // Pastikan template depan dan belakang sudah diunggah oleh admin
        if (! $program->hasCertificateTemplate()) {
            abort(404, 'Template sertifikat belum lengkap. Silakan hubungi admin.');
        }

        // Poin 9: Generate QR Code yang berisi URL sertifikat
        $url = $registration->certificateQrUrl(); 
        $qrcode = base64_encode(QrCode::format('svg')->size(120)->generate($url));

        // Poin 6: Load view PDF dengan data yang dibutuhkan
        $pdf = Pdf::loadView('certificate.pdf', [
            'registration' => $registration,
            'program' => $program,
            'qrcode' => $qrcode,
        ]);

        // Atur ukuran kertas ke A4 Landscape
        $pdf->setPaper('A4', 'landscape');

        // Jika ada query ?unduh=1 (dari tombol di my-class), maka otomatis Download
        if ($request->query('unduh')) {
            return $pdf->download('Sertifikat-' . $registration->certificate_number . '.pdf');
        }

        // Jika diakses langsung dari URL/QR Code, tampilkan di browser (Stream)
        return $pdf->stream('Sertifikat-' . $registration->certificate_number . '.pdf');
    }
}