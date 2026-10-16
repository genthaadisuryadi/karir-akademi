<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Support\Certificate\CertificatePdf;
use Illuminate\Http\Request;

/** Akses publik PDF sertifikat: /sertifikat/{nomor-sertifikat}. Ini juga alamat yang dimuat di QR Code. */
class CertificateController extends Controller
{
    public function show(Request $request, string $number)
    {
        $registration = Registration::with('program')
            ->where('certificate_number', strtolower(trim($number)))
            ->first();

        // Nomor yang belum terbit / template belum ada diperlakukan sama seperti tidak ditemukan.
        if (! $registration || ! $registration->isCertificateIssued() || ! $registration->program->hasCertificateTemplate()) {
            return response()->view('certificate.not-found', ['number' => $number], 404);
        }

        $pdf = CertificatePdf::render($registration);
        $disposition = $request->boolean('unduh') ? 'attachment' : 'inline';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="sertifikat-'.$registration->certificate_number.'.pdf"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
