<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRegistrationRequest;
use App\Models\Program;
use App\Models\Registration;
use Illuminate\Http\Request;
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

        // 1. Validasi Dasar (target_peserta dilonggarkan menjadi string biasa agar tidak bentrok)
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
            'max' => ':attribute terlalu panjang.'
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

        // Buang data persetujuan karena tidak masuk database
        unset($data['agree']);

        // 3. Mapping Data Sekolah yang Benar
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

        if ($type === 'phone') {
            $phoneVariants = Registration::phoneVariants($value);
            
            $programAsStaff = Program::whereIn('instructor_phone', $phoneVariants)
                                    ->orWhereIn('instructor_2_phone', $phoneVariants)
                                    ->orWhereIn('instructor_3_phone', $phoneVariants)
                                    ->orWhereIn('moderator_phone', $phoneVariants)
                                    ->orWhereIn('moderator_2_phone', $phoneVariants)
                                    ->orWhereIn('moderator_3_phone', $phoneVariants)
                                    ->first();
            
            if ($programAsStaff) {
                $matchedPhone = null;
                foreach ($phoneVariants as $variant) {
                    if ($programAsStaff->instructor_phone === $variant ||
                        $programAsStaff->instructor_2_phone === $variant ||
                        $programAsStaff->instructor_3_phone === $variant ||
                        $programAsStaff->moderator_phone === $variant ||
                        $programAsStaff->moderator_2_phone === $variant ||
                        $programAsStaff->moderator_3_phone === $variant) {
                        $matchedPhone = $variant;
                        break;
                    }
                }
                
                if ($matchedPhone) {
                    return redirect('/sertifikat/' . $programAsStaff->certificate_prefix . '-' . $matchedPhone);
                }
            }
        }

        $query = Registration::with('program');

        match ($type) {
            'email' => $query->whereRaw('LOWER(email) = ?', [$value]),
            'code' => $query->where('code', $value),
            'phone' => $query->whereIn('phone', Registration::phoneVariants($value)),
        };

        $matches = $query->get();

        if ($matches->isEmpty()) {
            return back()
                ->withInput($request->only('q'))
                ->with('error', 'Data pendaftaran tidak ditemukan. Periksa kembali email, nomor HP, atau nomor registrasi Anda.');
        }

        Registration::rememberInSession($matches->pluck('id'));

        return redirect(URL::temporarySignedRoute(
            'registration.my-class',
            now()->addMinutes(30),
            ['ids' => $matches->pluck('id')->implode(',')]
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

        $registrations = Registration::with('program')->whereIn('id', $ids)->latest()->get();

        abort_if($registrations->isEmpty(), 404);

        return view('registration.my-class', compact('registrations'));
    }

    private function generateCode(): string
    {
        do {
            $code = 'RKT-'.strtoupper(Str::random(6));
        } while (Registration::where('code', $code)->exists());

        return $code;
    }
}