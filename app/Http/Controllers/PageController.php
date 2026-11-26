<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PageController extends Controller
{
    public function subscription()
    {
        return view('pages.langganan');
    }

    public function partnership()
    {
        return view('pages.kerjasama');
    }

    public function certificate()
    {
        return view('pages.sertifikat');
    }

    public function certificateLookup(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:80']], [
            'code.required' => 'Kolom pencarian wajib diisi.',
        ]);

        $input = trim($data['code']);
        $digitsOnly = preg_replace('/[^0-9]/', '', $input);
        $isPhone = ! str_contains($input, '@')
            && preg_match('/^[0-9+\-\s()]+$/', $input)
            && strlen($digitsOnly) >= 9;

        // 1. Ambil SEMUA riwayat sertifikat Pemateri & Moderator jika input berupa nomor HP
        $staffEntries = $isPhone ? $this->findStaffEntries($input) : collect();

        // 2. Ambil riwayat sertifikat Peserta (mendukung kode registrasi, nomor HP, atau email)
        $registrations = Registration::with('program')
            ->where(function ($query) use ($input, $isPhone) {
                $query->where('code', strtoupper($input))
                    ->orWhereRaw('LOWER(email) = ?', [strtolower($input)]);

                if ($isPhone) {
                    $query->orWhereIn('phone', Registration::phoneVariants($input));
                }
            })
            ->latest()
            ->get();

        return view('pages.sertifikat', [
            'staffEntries' => $staffEntries,
            'registrations' => $registrations,
            'registration' => $registrations->first(),
            'searched' => true,
        ]);
    }

    private function findStaffEntries(string $phone): Collection
    {
        $phoneVariants = Registration::phoneVariants($phone);

        $programs = Program::where(function ($query) use ($phoneVariants) {
                $query->whereIn('instructor_phone', $phoneVariants)
                    ->orWhereIn('instructor_2_phone', $phoneVariants)
                    ->orWhereIn('instructor_3_phone', $phoneVariants)
                    ->orWhereIn('moderator_phone', $phoneVariants)
                    ->orWhereIn('moderator_2_phone', $phoneVariants)
                    ->orWhereIn('moderator_3_phone', $phoneVariants);
            })
            ->orderByDesc('id')
            ->get();

        $entries = collect();

        foreach ($programs as $program) {
            $slots = [
                ['role' => 'Pemateri',  'name' => $program->instructor,   'phone' => $program->instructor_phone],
                ['role' => 'Pemateri',  'name' => $program->instructor_2, 'phone' => $program->instructor_2_phone],
                ['role' => 'Pemateri',  'name' => $program->instructor_3, 'phone' => $program->instructor_3_phone],
                ['role' => 'Moderator', 'name' => $program->moderator,    'phone' => $program->moderator_phone],
                ['role' => 'Moderator', 'name' => $program->moderator_2,  'phone' => $program->moderator_2_phone],
                ['role' => 'Moderator', 'name' => $program->moderator_3,  'phone' => $program->moderator_3_phone],
            ];

            foreach ($slots as $slot) {
                if (filled($slot['phone']) && in_array($slot['phone'], $phoneVariants, true)) {
                    $certNumber = $program->certificate_prefix . '-' . $slot['phone'];
                    $hasTemplate = filled($program->certificate_prefix) && (
                        filled($program->certificate_front)
                        || ($slot['role'] === 'Pemateri' && filled($program->instructor_certificate_front))
                        || ($slot['role'] === 'Moderator' && filled($program->moderator_certificate_front))
                    );

                    $entries->push((object) [
                        'program' => $program,
                        'role' => $slot['role'],
                        'name' => $slot['name'] ?: $slot['role'],
                        'phone' => $slot['phone'],
                        'certificate_number' => $certNumber,
                        'view_url' => url('/sertifikat/' . $certNumber),
                        'download_url' => url('/sertifikat/' . $certNumber . '?unduh=1'),
                        'has_template' => $hasTemplate,
                    ]);
                    break;
                }
            }
        }

        return $entries;
    }
}