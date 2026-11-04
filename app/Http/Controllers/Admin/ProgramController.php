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
        $data['instructor_certificate_front'] = $this->storeTemplate($request, 'instructor_certificate_front', null);
        $data['instructor_certificate_back'] = $this->storeTemplate($request, 'instructor_certificate_back', null);
        $data['moderator_certificate_front'] = $this->storeTemplate($request, 'moderator_certificate_front', null);
        $data['moderator_certificate_back'] = $this->storeTemplate($request, 'moderator_certificate_back', null);

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
        $data['instructor_certificate_front'] = $this->storeTemplate($request, 'instructor_certificate_front', $program->instructor_certificate_front);
        $data['instructor_certificate_back'] = $this->storeTemplate($request, 'instructor_certificate_back', $program->instructor_certificate_back);
        $data['moderator_certificate_front'] = $this->storeTemplate($request, 'moderator_certificate_front', $program->moderator_certificate_front);
        $data['moderator_certificate_back'] = $this->storeTemplate($request, 'moderator_certificate_back', $program->moderator_certificate_back);

        $program->update($data);

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil diperbarui.');
    }
    
    public function toggleStatus(int $id)
    {
        $program = Program::findOrFail($id);
        $program->update(['is_active' => !$program->is_active]);

        $statusText = $program->is_active ? 'diaktifkan' : 'dinonaktifkan (diarsipkan)';
        return back()->with('success', "Kelas berhasil {$statusText}.");
    }

    public function toggleClose(int $id)
    {
        $program = Program::findOrFail($id);
        // Jika ditutup, pastikan is_active bernilai 1 agar tetap tampil di portofolio web
        $program->update([
            'is_closed' => !$program->is_closed,
            'is_active' => 1
        ]);

        $statusText = $program->is_closed ? 'ditutup' : 'dibuka kembali';
        return back()->with('success', "Pendaftaran kelas berhasil {$statusText}.");
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
        $this->deleteTemplate($program->moderator_certificate_front);
        $this->deleteTemplate($program->moderator_certificate_back);
        $program->delete();

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }

    private function validated(Request $request, ?Program $program = null): array
    {
        $id = $program?->id;
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
        
        $optionalTemplateRules = fn () => [
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
            
            // Templates
            'certificate_front' => $templateRules($program?->certificate_front),
            'certificate_back' => $templateRules($program?->certificate_back),
            'instructor_certificate_front' => $optionalTemplateRules(),
            'instructor_certificate_back' => $optionalTemplateRules(),
            'moderator_certificate_front' => $optionalTemplateRules(),
            'moderator_certificate_back' => $optionalTemplateRules(),
            
            // Staff Details
            'instructor' => ['nullable', 'string', 'max:255'],
            'instructor_phone' => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{7,13}$/'],
            'instructor_2' => ['nullable', 'string', 'max:255'],
            'instructor_2_phone' => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{7,13}$/'],
            'instructor_3' => ['nullable', 'string', 'max:255'],
            'instructor_3_phone' => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{7,13}$/'],
            'moderator' => ['nullable', 'string', 'max:255'],
            'moderator_phone' => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{7,13}$/'],

            // Class details
            'price_label' => ['nullable', 'string', 'max:30'],
            'jp' => ['required', 'integer', 'min:0', 'max:99'],
            'date_label' => ['required', 'string', 'max:150'],
            'time_label' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'benefit' => ['nullable', 'string', 'max:3000'],
            'whatsapp_group_link' => ['nullable', 'url:http,https', 'max:255'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        unset(
            $data['poster'], 
            $data['certificate_front'], 
            $data['certificate_back'],
            $data['instructor_certificate_front'],
            $data['instructor_certificate_back'],
            $data['moderator_certificate_front'],
            $data['moderator_certificate_back']
        );

        $data['short_title'] = ($data['short_title'] ?? null) ?: $data['title'];
        $data['price_label'] = ($data['price_label'] ?? null) ?: 'GRATIS';
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_closed'] = $request->boolean('is_closed');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['min_participants'] = null;
        $data['meta'] = Program::buildMeta($data);

        return $data;
    }

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
        if ($path && str_starts_with($path, 'uploads/posters/')) {
            @unlink(public_path($path));
        }
    }
}