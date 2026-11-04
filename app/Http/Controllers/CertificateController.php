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
        // 1. CEK APAKAH INI SERTIFIKAT PEMATERI ATAU MODERATOR (Semua Kolom)
        $programAsStaff = Program::whereRaw("CONCAT(certificate_prefix, '-', instructor_phone) = ?", [$number])
                                ->orWhereRaw("CONCAT(certificate_prefix, '-', instructor_2_phone) = ?", [$number])
                                ->orWhereRaw("CONCAT(certificate_prefix, '-', instructor_3_phone) = ?", [$number])
                                ->orWhereRaw("CONCAT(certificate_prefix, '-', moderator_phone) = ?", [$number])
                                ->orWhereRaw("CONCAT(certificate_prefix, '-', moderator_2_phone) = ?", [$number])
                                ->orWhereRaw("CONCAT(certificate_prefix, '-', moderator_3_phone) = ?", [$number])
                                ->first();

        if ($programAsStaff) {
            
            $staffName = 'Panitia / Pengajar';
            $urlPrefix = $programAsStaff->certificate_prefix . '-';
            $isModerator = false;
            
            if ($urlPrefix . $programAsStaff->instructor_phone === $number) {
                $staffName = $programAsStaff->instructor;
            } elseif ($urlPrefix . $programAsStaff->instructor_2_phone === $number) {
                $staffName = $programAsStaff->instructor_2;
            } elseif ($urlPrefix . $programAsStaff->instructor_3_phone === $number) {
                $staffName = $programAsStaff->instructor_3;
            } elseif ($urlPrefix . $programAsStaff->moderator_phone === $number) {
                $staffName = $programAsStaff->moderator;
                $isModerator = true; 
            } elseif ($urlPrefix . $programAsStaff->moderator_2_phone === $number) {
                $staffName = $programAsStaff->moderator_2;
                $isModerator = true; 
            } elseif ($urlPrefix . $programAsStaff->moderator_3_phone === $number) {
                $staffName = $programAsStaff->moderator_3;
                $isModerator = true; 
            }

            $frontTemplate = $isModerator 
                ? ($programAsStaff->moderator_certificate_front ?: $programAsStaff->certificate_front) 
                : ($programAsStaff->instructor_certificate_front ?: $programAsStaff->certificate_front);

            $backTemplate = $isModerator 
                ? ($programAsStaff->moderator_certificate_back ?: $programAsStaff->certificate_back) 
                : ($programAsStaff->instructor_certificate_back ?: $programAsStaff->certificate_back);

            return $this->renderCertificate($request, [
                'name' => $staffName,
                'number' => $number,
                'date' => now(), 
                'url' => url('/sertifikat/' . $number),
                'front' => $frontTemplate,
                'back' => $backTemplate,
            ]);
        }

        // 2. JIKA BUKAN PEMATERI/MODERATOR, CEK APAKAH INI SERTIFIKAT PESERTA
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