<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Registration;
use App\Models\User;
use App\Support\Certificate\CertificatePdf;
use App\Support\Certificate\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProgramDetailTest extends TestCase
{
    use RefreshDatabase;

    private const WA = 'https://chat.whatsapp.com/ABCDEF123456';

    private function program(array $overrides = []): Program
    {
        return Program::create($overrides + [
            'slug' => 'kelas-uji',
            'certificate_prefix' => 'kelas_uji',
            'title' => 'Kelas Uji',
            'short_title' => 'Kelas Uji',
            'audience' => 'guru',
            'price_label' => 'GRATIS',
            'jp' => 12,
            'date_label' => '10 Agustus 2026',
            'description' => "Halo **dunia**\n\n## Materi\n- Dasar AI\n- Praktik IoT",
            'benefit' => "- Sertifikat 12 JP\n- Ebook",
            'instructor' => 'Budi Santoso',
            'whatsapp_group_link' => self::WA,
            'is_active' => true,
        ]);
    }

    private function registerPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Siti', 'email' => 'siti@example.com', 'phone' => '081234567890',
            'school_name' => 'SMK 1', 'city' => 'Cirebon', 'agree' => '1',
        ];
    }

    /** Simpan template PNG sungguhan agar PDF bisa dibuat. */
    private function withTemplates(Program $program): Program
    {
        Storage::fake(Program::CERTIFICATE_DISK);

        foreach (['certificate_front' => [30, 60, 140], 'certificate_back' => [240, 240, 240]] as $field => [$r, $g, $b]) {
            $img = imagecreatetruecolor(1240, 877);
            imagefill($img, 0, 0, imagecolorallocate($img, $r, $g, $b));
            ob_start();
            imagepng($img);
            $path = Program::CERTIFICATE_DIR.'/'.$field.'.png';
            Storage::disk(Program::CERTIFICATE_DISK)->put($path, (string) ob_get_clean());
            $program->{$field} = $path;
        }
        $program->save();

        return $program;
    }

    private function registration(Program $program, array $overrides = []): Registration
    {
        return Registration::create($overrides + [
            'program_id' => $program->id,
            'code' => 'RKT-ABC123',
            'name' => 'Siti Aminah',
            'email' => 'sertifikat@example.com',
            'phone' => '083823051482',
            'school_name' => 'SMK 1',
            'city' => 'Cirebon',
            'status' => 'registered',
            'certificate_number' => Registration::makeCertificateNumber($program, '083823051482'),
        ]);
    }

    private function myClassUrl(Registration $registration): string
    {
        return URL::temporarySignedRoute('registration.my-class', now()->addMinutes(30), ['ids' => $registration->id]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function classPayload(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Bikin AI Agent',
            'short_title' => 'Bikin AI Agent',
            'audience' => 'umum',
            'certificate_prefix' => 'bikin_ai_agent',
            'certificate_front' => UploadedFile::fake()->image('depan.png', 1240, 877),
            'certificate_back' => UploadedFile::fake()->image('belakang.png', 1240, 877),
            'price_label' => 'GRATIS',
            'jp' => 12,
            'date_label' => '10 Agustus 2026',
        ];
    }

    // ---------------------------------------------------------------- halaman kelas

    public function test_description_and_benefit_are_rendered_as_markdown(): void
    {
        $this->program();

        $this->get('/pelatihan/kelas-uji')
            ->assertOk()
            ->assertSee('<strong>dunia</strong>', false)
            ->assertSee('<h2>Materi</h2>', false)
            ->assertSee('<li>Dasar AI</li>', false)
            ->assertSee('<li>Sertifikat 12 JP</li>', false)
            ->assertSee('12 JP')
            ->assertSee('Budi Santoso');
    }

    public function test_markdown_strips_raw_html_and_unsafe_links(): void
    {
        $this->program(['description' => "<script>alert(1)</script>\n\n[klik](javascript:alert(1)) [aman](https://example.com)"]);

        $this->get('/pelatihan/kelas-uji')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('href="javascript:', false)
            ->assertSee('href="https://example.com"', false);
    }

    public function test_home_uses_the_new_tagline(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Pilih jadwal pelatihan dan kembangkan keahlian kamu bersama Karir Akademi.')
            ->assertDontSee('keahlian AI, IoT');
    }

    public function test_footer_shows_ucic_and_rakit_ai_logos(): void
    {
        $this->get('/')->assertOk()->assertSee('UCIC')->assertSee('Rakit AI');
    }

    // ---------------------------------------------------------------- grup WhatsApp

    public function test_group_link_is_locked_before_registering(): void
    {
        $this->program();

        $this->get('/pelatihan/kelas-uji')
            ->assertOk()
            ->assertDontSee('chat.whatsapp.com')
            ->assertSee('Grup WhatsApp Terkunci')
            ->assertSee('Daftar Sekarang');
    }

    public function test_group_link_shows_right_after_registering(): void
    {
        $program = $this->program();

        $this->post("/pelatihan/{$program->slug}/daftar", $this->registerPayload())
            ->assertRedirect();
        $registration = Registration::query()->firstOrFail();

        $this->assertSame('registered', $registration->status);

        $this->get(route('registration.success', $registration->code))
            ->assertOk()
            ->assertSee('Mendaftar')
            ->assertSee('href="'.self::WA.'"', false);

        $this->get('/pelatihan/kelas-uji')
            ->assertOk()
            ->assertSee('href="'.self::WA.'"', false)
            ->assertSee('Buka Grup WhatsApp')
            ->assertDontSee('Daftar Sekarang');
    }

    public function test_registering_another_class_does_not_unlock_this_one(): void
    {
        $this->program();
        $other = $this->program(['slug' => 'kelas-lain', 'certificate_prefix' => 'kelas_lain', 'title' => 'Kelas Lain', 'short_title' => 'Kelas Lain']);

        $this->post("/pelatihan/{$other->slug}/daftar", $this->registerPayload())->assertRedirect();

        $this->get('/pelatihan/kelas-uji')->assertDontSee('chat.whatsapp.com');
        $this->get('/pelatihan/kelas-lain')
            ->assertSee(self::WA, false)
            ->assertSee('Buka Grup WhatsApp');
    }

    public function test_group_link_is_never_hidden_for_any_status_in_my_classes(): void
    {
        $program = $this->program();
        $registration = $this->registration($program);

        foreach (array_keys(Registration::STATUSES) as $status) {
            $registration->update(['status' => $status]);

            $this->get($this->myClassUrl($registration))
                ->assertOk()
                ->assertSee(Registration::STATUSES[$status])
                ->assertSee('href="'.self::WA.'"', false);
        }
    }

    public function test_group_link_is_visible_on_class_page_for_every_status(): void
    {
        $program = $this->program();
        $this->post("/pelatihan/{$program->slug}/daftar", $this->registerPayload())->assertRedirect();

        foreach (array_keys(Registration::STATUSES) as $status) {
            Registration::query()->update(['status' => $status]);

            $this->get('/pelatihan/kelas-uji')->assertSee('href="'.self::WA.'"', false);
        }
    }

    public function test_registered_class_without_link_shows_waiting_message(): void
    {
        $program = $this->program(['whatsapp_group_link' => null]);
        $this->post("/pelatihan/{$program->slug}/daftar", $this->registerPayload())->assertRedirect();

        $this->get('/pelatihan/kelas-uji')
            ->assertSee('Tautan Belum Tersedia')
            ->assertDontSee('chat.whatsapp.com');
    }

    // ---------------------------------------------------------------- status

    public function test_there_are_exactly_three_statuses(): void
    {
        $this->assertSame(
            ['Mendaftar', 'Terbit sertifikat', 'Tidak terbit sertifikat'],
            array_values(Registration::STATUSES)
        );
    }

    // ---------------------------------------------------------------- target peserta

    public function test_students_are_asked_for_school_name_and_class(): void
    {
        $program = $this->program(['slug' => 'kelas-siswa', 'certificate_prefix' => 'kelas_siswa', 'audience' => 'siswa']);

        $this->get("/pelatihan/{$program->slug}/daftar")
            ->assertOk()
            ->assertSee('Nama Sekolah')
            ->assertSee('name="class_name"', false)
            ->assertDontSee('Nama Instansi');

        $this->post("/pelatihan/{$program->slug}/daftar", $this->registerPayload())
            ->assertSessionHasErrors('class_name');

        $this->post("/pelatihan/{$program->slug}/daftar", $this->registerPayload(['class_name' => 'XI RPL 1']))
            ->assertRedirect();

        $this->assertDatabaseHas('registrations', ['school_name' => 'SMK 1', 'class_name' => 'XI RPL 1']);
    }

    public function test_teachers_are_asked_for_school_name_only(): void
    {
        $program = $this->program();

        $this->get("/pelatihan/{$program->slug}/daftar")
            ->assertOk()
            ->assertSee('Nama Sekolah')
            ->assertDontSee('name="class_name"', false)
            ->assertDontSee('Nama Instansi');

        $this->post("/pelatihan/{$program->slug}/daftar", $this->registerPayload(['school_name' => '']))
            ->assertSessionHasErrors('school_name');

        $this->post("/pelatihan/{$program->slug}/daftar", $this->registerPayload())->assertRedirect();
        $this->assertDatabaseHas('registrations', ['school_name' => 'SMK 1', 'class_name' => null]);
    }

    public function test_public_participants_are_asked_for_institution_name(): void
    {
        $program = $this->program(['slug' => 'kelas-umum', 'certificate_prefix' => 'kelas_umum', 'audience' => 'umum']);

        $this->get("/pelatihan/{$program->slug}/daftar")
            ->assertOk()
            ->assertSee('Nama Instansi')
            ->assertDontSee('Nama Sekolah')
            ->assertDontSee('name="class_name"', false);

        $this->post("/pelatihan/{$program->slug}/daftar", $this->registerPayload(['school_name' => '']))
            ->assertSessionHasErrors('school_name');

        $this->post("/pelatihan/{$program->slug}/daftar", $this->registerPayload(['school_name' => 'PT Maju']))
            ->assertRedirect();
        $this->assertDatabaseHas('registrations', ['school_name' => 'PT Maju']);
    }

    // ---------------------------------------------------------------- nomor sertifikat

    public function test_certificate_number_uses_prefix_and_whatsapp_number(): void
    {
        $program = $this->program(['certificate_prefix' => 'bikin_ai_agent']);

        $this->post("/pelatihan/{$program->slug}/daftar", $this->registerPayload(['phone' => '+6283823051482']))
            ->assertRedirect();

        $this->assertDatabaseHas('registrations', [
            'phone' => '083823051482',
            'certificate_number' => 'bikin_ai_agent-083823051482',
        ]);
    }

    public function test_same_whatsapp_number_cannot_register_twice_in_one_class(): void
    {
        $program = $this->program();

        $this->post("/pelatihan/{$program->slug}/daftar", $this->registerPayload())->assertRedirect();
        $this->post("/pelatihan/{$program->slug}/daftar", $this->registerPayload([
            'email' => 'lain@example.com', 'phone' => '6281234567890',
        ]))->assertSessionHasErrors('phone');

        $this->assertSame(1, Registration::count());
    }

    // ---------------------------------------------------------------- admin: kelas baru

    public function test_admin_creates_a_class_with_prefix_and_two_templates(): void
    {
        Storage::fake(Program::CERTIFICATE_DISK);

        $this->actingAs($this->admin())
            ->post(route('admin.kelas.store'), $this->classPayload(['whatsapp_group_link' => 'https://example.com/kelas']))
            ->assertRedirect(route('admin.kelas.index'));

        $program = Program::where('slug', 'bikin-ai-agent')->firstOrFail();
        $this->assertSame('umum', $program->audience);
        $this->assertSame('bikin_ai_agent', $program->certificate_prefix);
        $this->assertTrue($program->hasCertificateTemplate());
        Storage::disk(Program::CERTIFICATE_DISK)->assertExists([$program->certificate_front, $program->certificate_back]);
    }

    public function test_certificate_prefix_must_be_unique_and_without_spaces(): void
    {
        Storage::fake(Program::CERTIFICATE_DISK);
        $this->program(['certificate_prefix' => 'bikin_ai_agent']);

        $this->actingAs($this->admin())
            ->post(route('admin.kelas.store'), $this->classPayload())
            ->assertSessionHasErrors('certificate_prefix');

        $this->actingAs($this->admin())
            ->post(route('admin.kelas.store'), $this->classPayload(['certificate_prefix' => 'bikin ai agent']))
            ->assertSessionHasErrors('certificate_prefix');

        $this->actingAs($this->admin())
            ->post(route('admin.kelas.store'), $this->classPayload(['certificate_prefix' => 'bikin-ai']))
            ->assertSessionHasErrors('certificate_prefix');

        $this->assertSame(1, Program::count());
    }

    public function test_both_certificate_templates_are_required_and_must_be_png(): void
    {
        Storage::fake(Program::CERTIFICATE_DISK);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.kelas.store'), $this->classPayload(['certificate_back' => null]))
            ->assertSessionHasErrors('certificate_back');

        $this->actingAs($admin)
            ->post(route('admin.kelas.store'), $this->classPayload([
                'certificate_front' => UploadedFile::fake()->image('depan.jpg', 1240, 877),
            ]))
            ->assertSessionHasErrors('certificate_front');

        $this->assertSame(0, Program::count());
    }

    public function test_admin_can_save_a_non_whatsapp_class_link(): void
    {
        Storage::fake(Program::CERTIFICATE_DISK);

        $this->actingAs($this->admin())
            ->post(route('admin.kelas.store'), $this->classPayload([
                'title' => 'Kelas dengan tautan umum',
                'short_title' => 'Kelas tautan umum',
                'audience' => 'guru',
                'certificate_prefix' => 'kelas_tautan_umum',
                'whatsapp_group_link' => 'https://example.com/kelas',
            ]))
            ->assertRedirect(route('admin.kelas.index'));

        $this->assertDatabaseHas('programs', [
            'slug' => 'kelas-dengan-tautan-umum',
            'whatsapp_group_link' => 'https://example.com/kelas',
        ]);
    }

    public function test_prefix_is_locked_once_the_class_has_registrations(): void
    {
        $program = $this->withTemplates($this->program(['certificate_prefix' => 'awal']));
        $this->registration($program);

        $this->actingAs($this->admin())
            ->put(route('admin.kelas.update', $program->id), [
                'title' => 'Kelas Uji', 'audience' => 'guru', 'certificate_prefix' => 'diubah',
                'jp' => 12, 'date_label' => '10 Agustus 2026',
            ])
            ->assertRedirect(route('admin.kelas.index'));

        $this->assertSame('awal', $program->fresh()->certificate_prefix);
    }

    // ---------------------------------------------------------------- admin: status

    public function test_admin_issues_a_certificate_by_changing_status(): void
    {
        $program = $this->withTemplates($this->program());
        $registration = $this->registration($program);

        $this->actingAs($this->admin())
            ->patch(route('admin.pendaftar.status', $registration->id), ['status' => 'certificate_issued'])
            ->assertSessionHas('success');

        $registration->refresh();
        $this->assertSame('certificate_issued', $registration->status);
        $this->assertNotNull($registration->certificate_issued_at);
    }

    public function test_certificate_cannot_be_issued_without_templates(): void
    {
        $registration = $this->registration($this->program());

        $this->actingAs($this->admin())
            ->patch(route('admin.pendaftar.status', $registration->id), ['status' => 'certificate_issued'])
            ->assertSessionHas('error');

        $this->assertSame('registered', $registration->fresh()->status);
    }

    public function test_legacy_registration_with_a_clashing_number_cannot_be_issued(): void
    {
        $program = $this->withTemplates($this->program());
        $this->registration($program);
        $legacy = $this->registration($program, [
            'code' => 'RKT-OLD123', 'email' => 'lama@example.com', 'phone' => '6283823051482', 'certificate_number' => null,
        ]);

        $this->actingAs($this->admin())
            ->patch(route('admin.pendaftar.status', $legacy->id), ['status' => 'certificate_issued'])
            ->assertSessionHas('error');

        $this->assertSame('registered', $legacy->fresh()->status);
    }

    public function test_admin_can_set_the_not_issued_status(): void
    {
        $registration = $this->registration($this->program());

        $this->actingAs($this->admin())
            ->patch(route('admin.pendaftar.status', $registration->id), ['status' => 'certificate_not_issued'])
            ->assertSessionHas('success');

        $this->assertSame('certificate_not_issued', $registration->fresh()->status);
    }

    // ---------------------------------------------------------------- PDF sertifikat

    public function test_certificate_pdf_is_served_at_the_certificate_url(): void
    {
        $program = $this->withTemplates($this->program(['certificate_prefix' => 'bikin_ai_agent']));
        $registration = $this->registration($program, ['status' => 'certificate_issued', 'certificate_issued_at' => '2026-10-16 10:00:00']);

        $response = $this->get('/sertifikat/bikin_ai_agent-083823051482')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));

        $pdf = $response->getContent();
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('/Count 2', $pdf);
        $this->assertSame(2, substr_count($pdf, '/Subtype /Image'));
        $this->assertStringContainsString('/Title (Sertifikat Siti Aminah)', $pdf);

        $this->get('/sertifikat/BIKIN_AI_AGENT-083823051482')->assertOk();
        $this->get($registration->certificateUrl(true))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="sertifikat-bikin_ai_agent-083823051482.pdf"');
    }

    public function test_certificate_pdf_content_has_name_number_date_and_qr(): void
    {
        $program = $this->withTemplates($this->program(['certificate_prefix' => 'bikin_ai_agent']));
        $registration = $this->registration($program, ['status' => 'certificate_issued', 'certificate_issued_at' => '2026-10-16 10:00:00']);

        $pdf = CertificatePdf::render($registration->load('program'));

        // Aliran konten halaman 1 dikompres; ambil dan buka kompresinya.
        preg_match_all('/stream\n(.*?)\nendstream/s', $pdf, $m);
        $texts = '';
        foreach ($m[1] as $chunk) {
            $plain = @gzuncompress($chunk);
            if ($plain !== false && str_contains($plain, 'BT')) {
                $texts .= $plain;
            }
        }

        $this->assertStringContainsString('(Siti Aminah) Tj', $texts);
        $this->assertStringContainsString('(bikin_ai_agent-083823051482) Tj', $texts);
        $this->assertStringContainsString('(16 Oktober 2026) Tj', $texts);
        $this->assertStringContainsString(' re', $texts); // modul QR
    }

    public function test_qr_code_points_to_the_public_certificate_url(): void
    {
        config(['certificate.base_url' => 'http://karirakademi.id/']);
        $registration = $this->registration($this->program(['certificate_prefix' => 'bikin_ai_agent']));

        $this->assertSame(
            'http://karirakademi.id/sertifikat/bikin_ai_agent-083823051482',
            $registration->certificateQrUrl()
        );

        $matrix = QrCode::matrix($registration->certificateQrUrl());
        $this->assertSame(33, count($matrix)); // QR versi 4
        $this->assertTrue($matrix[0][0] && $matrix[0][6] && $matrix[6][0]); // sudut pola finder
    }

    public function test_unknown_certificate_url_shows_not_found_message_without_search_button(): void
    {
        $this->get('/sertifikat/tidak-ada-081234567890')
            ->assertNotFound()
            ->assertSee('Sertifikat tidak ditemukan')
            ->assertSee('tidak-ada-081234567890')
            ->assertDontSee('Cari');
    }

    public function test_certificate_that_is_not_issued_is_treated_as_not_found(): void
    {
        $program = $this->withTemplates($this->program(['certificate_prefix' => 'bikin_ai_agent']));
        $registration = $this->registration($program);

        foreach (['registered', 'certificate_not_issued'] as $status) {
            $registration->update(['status' => $status]);

            $this->get('/sertifikat/bikin_ai_agent-083823051482')
                ->assertNotFound()
                ->assertSee('Sertifikat tidak ditemukan');
        }
    }

    public function test_download_button_in_my_classes_only_when_certificate_is_issued(): void
    {
        $program = $this->withTemplates($this->program(['certificate_prefix' => 'bikin_ai_agent']));
        $registration = $this->registration($program);

        $this->get($this->myClassUrl($registration))
            ->assertOk()
            ->assertDontSee('Unduh Sertifikat');

        $registration->update(['status' => 'certificate_issued']);

        $this->get($this->myClassUrl($registration))
            ->assertOk()
            ->assertSee('Unduh Sertifikat')
            ->assertSee('href="'.route('certificate.show', 'bikin_ai_agent-083823051482').'?unduh=1"', false);
    }

    public function test_certificate_page_keeps_working(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Unduh Sertifikat');
        $this->get(route('certificate'))->assertOk();
    }

    public function test_registration_success_returns_to_the_class_page(): void
    {
        $program = $this->program();

        $this->post("/pelatihan/{$program->slug}/daftar", $this->registerPayload())->assertRedirect();
        $registration = Registration::query()->firstOrFail();

        $this->get(route('registration.success', $registration->code))
            ->assertOk()
            ->assertSee('Kembali ke Halaman Kelas')
            ->assertSee('href="'.route('program.show', $program).'"', false);
    }
}
