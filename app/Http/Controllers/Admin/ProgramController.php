<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Kelola kelas / program pelatihan. */
class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $programs = Program::withCount('registrations')
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.programs.index', compact('programs'));
    }

    public function create()
    {
        return view('admin.programs.form', ['program' => new Program]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['poster'] = $this->storePoster($request, null);
        $data['certificate_front'] = $this->storeTemplate($request, 'certificate_front', null);
        $data['certificate_back'] = $this->storeTemplate($request, 'certificate_back', null);
        
        // Simpan template khusus pemateri
        $data['instructor_certificate_front'] = $this->storeTemplate($request, 'instructor_certificate_front', null);
        $data['instructor_certificate_back'] = $this->storeTemplate($request, 'instructor_certificate_back', null);

        Program::create($data);

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        return view('admin.programs.form', ['program' => Program::findOrFail($id)]);
    }

    public function update(Request $request, int $id)
    {
        $program = Program::findOrFail($id);

        $data = $this->validated($request, $program);
        $data['poster'] = $this->storePoster($request, $program->poster);
        $data['certificate_front'] = $this->storeTemplate($request, 'certificate_front', $program->certificate_front);
        $data['certificate_back'] = $this->storeTemplate($request, 'certificate_back', $program->certificate_back);
        
        // Simpan template khusus pemateri
        $data['instructor_certificate_front'] = $this->storeTemplate($request, 'instructor_certificate_front', $program->instructor_certificate_front);
        $data['instructor_certificate_back'] = $this->storeTemplate($request, 'instructor_certificate_back', $program->instructor_certificate_back);

        $program->update($data);

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $program = Program::withCount('registrations')->findOrFail($id);

        if ($program->registrations_count > 0) {
            return back()->with('error', "Kelas ini sudah memiliki {$program->registrations_count} pendaftar sehingga tidak dapat dihapus. Nonaktifkan saja agar tidak tampil di halaman publik.");
        }

        $this->deletePoster($program->poster);
        $this->deleteTemplate($program->certificate_front);
        $this->deleteTemplate($program->certificate_back);
        $this->deleteTemplate($program->instructor_certificate_front);
        $this->deleteTemplate($program->instructor_certificate_back);
        $program->delete();

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }

    private function validated(Request $request, ?Program $program = null): array
    {
        $id = $program?->id;

        // Prefix dikunci setelah ada pendaftar karena sudah menjadi bagian nomor sertifikat dan QR Code.
        $prefixLocked = $program !== null && filled($program->certificate_prefix) && $program->registrations()->exists();

        $request->merge([
            'slug' => Str::slug((string) ($request->input('slug') ?: $request->input('title'))),
            'certificate_prefix' => $prefixLocked
                ? $program->certificate_prefix
                : strtolower(trim((string) $request->input('certificate_prefix'))),
        ]);

        $template = config('certificate.template');
        $templateRules = fn (?string $current = null) => [
            filled($current) ? 'nullable' : 'required',
            'file', 'mimes:png', 'mimetypes:image/png',
            'max:'.$template['max_kb'],
            'dimensions:min_width='.$template['min_width'].',min_height='.$template['min_height'].',max_width=3600,max_height=2600',
        ];
        
        // Aturan untuk template pemateri (opsional)
        $instructorTemplateRules = fn () => [
            'nullable',
            'file', 'mimes:png', 'mimetypes:image/png',
            'max:'.$template['max_kb'],
            'dimensions:min_width='.$template['min_width'].',min_height='.$template['min_height'].',max_width=3600,max_height=2600',
        ];

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'short_title' => ['nullable', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:200', Rule::unique('programs', 'slug')->ignore($id)],
            'audience' => ['required', Rule::in(array_keys(Program::AUDIENCES))],
            'certificate_prefix' => [
                'required', 'string', 'max:60',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('programs', 'certificate_prefix')->ignore($id),
            ],
            'certificate_front' => $templateRules($program?->certificate_front),
            'certificate_back' => $templateRules($program?->certificate_back),
            
            // Validasi field pemateri
            'instructor_certificate_front' => $instructorTemplateRules(),
            'instructor_certificate_back' => $instructorTemplateRules(),
            'instructor_phone' => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{7,13}$/'],

            'price_label' => ['nullable', 'string', 'max:30'],
            'jp' => ['required', 'integer', 'min:0', 'max:99'],
            'date_label' => ['required', 'string', 'max:150'],
            'time_label' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'benefit' => ['nullable', 'string', 'max:3000'],
            'instructor' => ['nullable', 'string', 'max:255'],
            'whatsapp_group_link' => ['nullable', 'url:http,https', 'max:255'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'required' => ':attribute wajib diisi.',
            'unique' => ':attribute sudah dipakai kelas lain.',
            'integer' => ':attribute harus berupa angka.',
            'min' => ':attribute tidak boleh kurang dari :min.',
            'max' => ':attribute melebihi batas yang diizinkan.',
            'image' => 'Poster harus berupa gambar.',
            'mimes' => 'Poster harus berformat JPG, PNG, atau WEBP.',
            'poster.max' => 'Ukuran poster maksimal 2 MB.',
            'certificate_prefix.regex' => 'Prefix sertifikat hanya boleh huruf kecil, angka, dan garis bawah (_), tanpa spasi.',
            'certificate_front.mimes' => 'Template depan harus berformat PNG.',
            'certificate_back.mimes' => 'Template belakang harus berformat PNG.',
            'certificate_front.mimetypes' => 'Template depan harus berformat PNG.',
            'certificate_back.mimetypes' => 'Template belakang harus berformat PNG.',
            'instructor_certificate_front.mimes' => 'Template pemateri depan harus berformat PNG.',
            'instructor_certificate_back.mimes' => 'Template pemateri belakang harus berformat PNG.',
            'instructor_phone.regex' => 'Nomor WhatsApp Pemateri tidak valid.',
            'dimensions' => ':attribute harus berukuran minimal 1000 x 700 piksel dan maksimal 3600 x 2600 piksel.',
            'in' => 'Pilihan :attribute tidak valid.',
            'url' => ':attribute harus berupa URL yang valid.',
        ], [
            'title' => 'Judul kelas',
            'short_title' => 'Judul pada kartu',
            'slug' => 'Slug URL',
            'audience' => 'Target peserta',
            'certificate_prefix' => 'Prefix sertifikat',
            'certificate_front' => 'Template sertifikat depan',
            'certificate_back' => 'Template sertifikat belakang',
            'instructor_certificate_front' => 'Template pemateri (Depan)',
            'instructor_certificate_back' => 'Template pemateri (Belakang)',
            'instructor_phone' => 'Nomor WhatsApp Pemateri',
            'price_label' => 'Label harga',
            'jp' => 'Jam pelajaran (JP)',
            'date_label' => 'Tanggal',
            'time_label' => 'Jam',
            'location' => 'Lokasi',
            'description' => 'Deskripsi',
            'benefit' => 'Benefit',
            'instructor' => 'Pengajar',
            'whatsapp_group_link' => 'Tautan kelas',
            'quota' => 'Kuota',
            'sort_order' => 'Urutan',
            'poster' => 'Poster',
        ]);

        unset(
            $data['poster'], 
            $data['certificate_front'], 
            $data['certificate_back'],
            $data['instructor_certificate_front'],
            $data['instructor_certificate_back']
        );

        $data['short_title'] = ($data['short_title'] ?? null) ?: $data['title'];
        $data['price_label'] = ($data['price_label'] ?? null) ?: 'GRATIS';
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['min_participants'] = null;
        $data['meta'] = Program::buildMeta($data);

        return $data;
    }

    /** Simpan poster ke public/uploads/posters (tanpa perlu storage:link). */
    private function storePoster(Request $request, ?string $old): ?string
    {
        if (! $request->hasFile('poster')) {
            return $old;
        }

        $file = $request->file('poster');
        $name = Str::uuid()->toString().'.'.$file->extension();
        $file->move(public_path('uploads/posters'), $name);

        $this->deletePoster($old);

        return 'uploads/posters/'.$name;
    }

    /**
     * Simpan template sertifikat (PNG) di penyimpanan privat. Berkas diberi nama acak sehingga
     * cache JPEG-nya otomatis tidak basi saat template diganti.
     */
    private function storeTemplate(Request $request, string $field, ?string $old): ?string
    {
        if (! $request->hasFile($field)) {
            return $old;
        }

        $path = $request->file($field)->storeAs(
            Program::CERTIFICATE_DIR,
            Str::uuid()->toString().'.png',
            Program::CERTIFICATE_DISK
        );

        $this->deleteTemplate($old);

        return $path ?: $old;
    }

    private function deleteTemplate(?string $path): void
    {
        if ($path) {
            Storage::disk(Program::CERTIFICATE_DISK)->delete([$path, $path.'.jpg']);
        }
    }

    private function deletePoster(?string $path): void
    {
        // Hanya hapus file unggahan admin; poster bawaan (images/...) dibiarkan.
        if ($path && str_starts_with($path, 'uploads/posters/')) {
            @unlink(public_path($path));
        }
    }
}