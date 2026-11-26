<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRegistrationRequest;
use App\Models\Program;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    public function create(Program $program)
    {
        abort_unless($program->is_active, 404);

        return view('registration.create', compact('program'));
    }

    public function store(Request $request, Program $program)
    {
        abort_unless($program->is_active, 404);

        if ($program->isFull()) {
            return back()->withInput()->withErrors(['email' => 'Maaf, kuota program ini sudah penuh.']);
        }

        // 1. Validasi Dasar
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('registrations')->where('program_id', $program->id),
            ],
            'phone' => ['required', 'regex:/^(\+62|62|0)8[0-9]{7,13}$/'],
            'city' => ['required', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:120'],
            'motivation' => ['nullable', 'string', 'max:1000'],
            'target_peserta' => ['required', 'string'],
            'agree' => ['accepted'],
        ];

        // 2. Deteksi Target Peserta yang Fleksibel
        $target = strtolower($request->input('target_peserta'));

        if (str_contains($target, 'siswa') || str_contains($target, 'mahasiswa')) {
            $rules['nama_sekolah'] = ['required', 'string', 'max:150'];
            $rules['kelas'] = ['required', 'string', 'max:50'];
        } elseif (str_contains($target, 'guru')) {
            $rules['nama_sekolah'] = ['required', 'string', 'max:150'];
        } else {
            $rules['instansi'] = ['required', 'string', 'max:150'];
        }

        $data = $request->validate($rules, [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email tidak valid.',
            'unique' => ':attribute ini sudah terdaftar pada program ini.',
            'phone.regex' => 'Nomor WhatsApp tidak valid (contoh: 081234567890).',
            'accepted' => 'Anda harus menyetujui pernyataan ini di bagian bawah.',
            'max' => ':attribute terlalu panjang.',
        ], [
            'name' => 'Nama lengkap',
            'email' => 'Email',
            'phone' => 'Nomor WhatsApp',
            'target_peserta' => 'Target Peserta',
            'nama_sekolah' => 'Asal sekolah atau perguruan tinggi',
            'kelas' => 'Kelas/Prodi',
            'instansi' => 'Nama instansi',
            'city' => 'Kota/Kabupaten',
            'position' => 'Mata pelajaran/jabatan',
            'motivation' => 'Motivasi',
            'agree' => 'Pernyataan Persetujuan',
        ]);

        unset($data['agree']);

        // 3. Mapping Data Sekolah
        $data['school_name'] = $request->input('nama_sekolah') ?: $request->input('instansi') ?: null;
        $data['class_name'] = $request->input('kelas') ?: null;

        unset($data['nama_sekolah'], $data['instansi'], $data['kelas'], $data['target_peserta']);

        // 4. Normalisasi dan Simpan
        $data['phone'] = Registration::normalizePhone($data['phone']);

        if (Registration::where('program_id', $program->id)->whereIn('phone', Registration::phoneVariants($data['phone']))->exists()) {
            return back()->withInput()->withErrors(['phone' => 'Nomor WhatsApp ini sudah terdaftar pada program ini.']);
        }

        $registration = Registration::create($data + [
            'program_id' => $program->id,
            'code' => $this->generateCode(),
            'status' => 'registered',
            'certificate_number' => Registration::makeCertificateNumber($program, $data['phone']),
        ]);

        Registration::rememberInSession([$registration->id]);

        return redirect()->route('registration.success', $registration->code);
    }

    public function success(string $code)
    {
        $registration = Registration::with('program')->where('code', $code)->firstOrFail();

        return view('registration.success', compact('registration'));
    }

    public function search()
    {
        return view('registration.search');
    }

    public function find(SearchRegistrationRequest $request)
    {
        ['type' => $type, 'value' => $value] = $request->identifier();

        // 1. Cek semua riwayat Pemateri & Moderator jika input berupa nomor HP
        $staffEntries = $type === 'phone' ? $this->findStaffEntries($value) : collect();

        // 2. Cek semua riwayat pendaftaran Peserta
        $query = Registration::with('program');

        match ($type) {
            'email' => $query->whereRaw('LOWER(email) = ?', [$value]),
            'code' => $query->where('code', $value),
            'phone' => $query->whereIn('phone', Registration::phoneVariants($value)),
        };

        $matches = $query->get();

        if ($matches->isEmpty() && $staffEntries->isEmpty()) {
            return back()
                ->withInput($request->only('q'))
                ->with('error', 'Data pendaftaran atau riwayat pengajar tidak ditemukan. Periksa kembali email, nomor HP, atau nomor registrasi Anda.');
        }

        $params = [];

        if ($matches->isNotEmpty()) {
            Registration::rememberInSession($matches->pluck('id'));
            $params['ids'] = $matches->pluck('id')->implode(',');
        }

        if ($staffEntries->isNotEmpty()) {
            $params['staff_phone'] = Registration::normalizePhone($value);
        }

        return redirect(URL::temporarySignedRoute(
            'registration.my-class',
            now()->addMinutes(30),
            $params
        ));
    }

    public function myClass(Request $request)
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('registration.search')
                ->with('error', 'Tautan sudah kedaluwarsa atau tidak valid. Silakan cari kembali data pendaftaran Anda.');
        }

        $ids = collect(explode(',', (string) $request->query('ids')))
            ->filter(fn ($id) => ctype_digit($id))
            ->map(fn ($id) => (int) $id)
            ->take(20);

        $registrations = $ids->isNotEmpty()
            ? Registration::with('program')->whereIn('id', $ids)->latest()->get()
            : collect();

        $staffPhone = trim((string) $request->query('staff_phone'));
        $staffEntries = $staffPhone !== ''
            ? $this->findStaffEntries($staffPhone)
            : collect();

        abort_if($registrations->isEmpty() && $staffEntries->isEmpty(), 404);

        return view('registration.my-class', compact('registrations', 'staffEntries'));
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

    private function generateCode(): string
    {
        do {
            $code = 'RKT-'.strtoupper(Str::random(6));
        } while (Registration::where('code', $code)->exists());

        return $code;
    }
}