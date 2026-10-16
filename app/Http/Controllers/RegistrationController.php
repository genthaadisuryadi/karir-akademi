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
            'agree' => ['accepted'],
            // Siswa: nama sekolah + kelas. Guru: nama sekolah. Umum: nama instansi.
            'school_name' => ['required', 'string', 'max:150'],
        ];

        if ($program->isForStudents()) {
            $rules['class_name'] = ['required', 'string', 'max:50'];
        }

        $data = $request->validate($rules, [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email tidak valid.',
            'unique' => ':attribute ini sudah terdaftar pada program ini.',
            'phone.regex' => 'Nomor WhatsApp tidak valid (contoh: 081234567890).',
            'accepted' => 'Anda harus menyetujui pernyataan ini.',
            'max' => ':attribute terlalu panjang.',
        ], [
            'name' => 'Nama lengkap',
            'email' => 'Email',
            'phone' => 'Nomor WhatsApp',
            'school_name' => $program->isForPublic() ? 'Nama instansi' : 'Nama sekolah',
            'class_name' => 'Kelas',
            'city' => 'Kota/Kabupaten',
            'position' => 'Mata pelajaran/jabatan',
            'motivation' => 'Motivasi',
        ]);

        unset($data['agree']);

        // Nomor WhatsApp disimpan seragam (0812...) karena dipakai sebagai bagian nomor sertifikat.
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

        // Pendaftaran diingat di sesi ini agar tautan grup langsung tampil di halaman kelas.
        Registration::rememberInSession([$registration->id]);

        return redirect()->route('registration.success', $registration->code);
    }

    public function success(string $code)
    {
        $registration = Registration::with('program')->where('code', $code)->firstOrFail();

        return view('registration.success', compact('registration'));
    }

    /** Form pencarian kelas yang sudah didaftar. */
    public function search()
    {
        return view('registration.search');
    }

    /**
     * Proses pencarian. Satu kolom: bisa diisi email, nomor HP, atau nomor registrasi.
     * Jenis input dikenali otomatis oleh SearchRegistrationRequest::identifier().
     */
    public function find(SearchRegistrationRequest $request)
    {
        ['type' => $type, 'value' => $value] = $request->identifier();

        $query = Registration::with('program');

        match ($type) {
            'email' => $query->whereRaw('LOWER(email) = ?', [$value]),
            'code' => $query->where('code', $value),
            'phone' => $query->whereIn('phone', Registration::phoneVariants($value)),
        };

        $matches = $query->get();

        if ($matches->isEmpty()) {
            // Pesan sengaja generik agar tidak membocorkan jenis data yang salah.
            return back()
                ->withInput($request->only('q'))
                ->with('error', 'Data pendaftaran tidak ditemukan. Periksa kembali email, nomor HP, atau nomor registrasi Anda.');
        }

        // Pendaftaran yang ditemukan membuka link grup WhatsApp di browser ini.
        Registration::rememberInSession($matches->pluck('id'));

        // Tautan bertanda tangan (signed URL) berlaku 30 menit dan tidak bisa dimanipulasi.
        return redirect(URL::temporarySignedRoute(
            'registration.my-class',
            now()->addMinutes(30),
            ['ids' => $matches->pluck('id')->implode(',')]
        ));
    }

    /** Detail kelas yang sudah didaftar (deskripsi + link grup WhatsApp). */
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
