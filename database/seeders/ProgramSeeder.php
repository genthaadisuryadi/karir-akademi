<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            [
                'slug' => 'ai-iot-robotik-coding-sma-smk',
                'certificate_prefix' => 'ai_iot_robotik_coding_sma_smk',
                'title' => 'Pelatihan AI IoT Robotik Coding SMA/SMK',
                'short_title' => 'Pelatihan AI IoT Robotik Coding SMA/SMK',
                'audience' => 'siswa',
                'poster' => 'images/poster-siswa.jpg',
                'jp' => 3,
                'date_label' => 'Menyesuaikan Jadwal Sekolah',
                'time_label' => null,
                'location' => 'Kampus 2 UCIC - Lab AI IoT, Gedung CIC HUB',
                'meta' => [
                    ['icon' => 'calendar', 'text' => 'Menyesuaikan Jadwal Sekolah'],
                ],
                'description' => "Transformasi kemampuan siswa menjadi **inovator teknologi** di bidang AI, IoT, Robotik, dan Coding.\n\n## Format Pelatihan\n- Durasi **2 jam** dengan akses materi online\n- Pelaksanaan menyesuaikan jadwal sekolah",
                'benefit' => "- Sertifikat pelatihan 6 JP\n- Silabus untuk 1 tahun ajaran bagi sekolah\n- Target akhir: peserta mampu membuat proyek IoT dan robotik sendiri",
                'sort_order' => 1,
            ],
            [
                'slug' => 'ai-iot-robotik-coding-guru-batch-1',
                'certificate_prefix' => 'ai_iot_robotik_coding_guru_batch_1',
                'title' => 'Pelatihan AI IoT Robotik Coding Bagi Guru Batch #1',
                'short_title' => 'Pelatihan AI IoT Robotik Coding Bagi Guru Batch #1',
                'audience' => 'guru',
                'poster' => 'images/poster-guru-online.jpg',
                'jp' => 12,
                'date_label' => '10 - 12 Agustus 2026',
                'time_label' => '19.00 - 21.00 WIB',
                'location' => 'Online (Live)',
                'meta' => [
                    ['icon' => 'calendar', 'text' => '10 - 12 Agustus 2026'],
                    ['icon' => 'clock', 'text' => '19.00 - 21.00 WIB'],
                ],
                'description' => "Transformasi peran guru menjadi **inovator teknologi** dengan panduan merintis, mengelola, dan mengajar program ekstrakurikuler **AI + IoT + Robotik + Coding**.\n\nKhusus untuk guru SMA / SMK / MA. Pelatihan dilaksanakan secara online (live).",
                'benefit' => "- Sertifikat pelatihan 12 JP\n- Ebook untuk pengajaran di sekolah\n- Silabus untuk 1 tahun ajaran bagi sekolah\n- Guru mampu membuat sendiri proyek AI, IoT, dan Robotik",
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'bootcamp-guru-mengajar-dengan-ai-batch-1',
                'certificate_prefix' => 'bootcamp_guru_mengajar_dengan_ai_batch_1',
                'title' => 'Bootcamp Bagi Guru Mengajar Dengan AI Batch #1',
                'short_title' => 'Bootcamp Bagi Guru Mengajar Dengan AI Batch #1',
                'audience' => 'guru',
                'poster' => 'images/poster-guru-bootcamp.jpg',
                'jp' => 6,
                'date_label' => '20 Juli 2026',
                'time_label' => '09.00 - 15.00 WIB',
                'location' => 'UCIC Kampus 2 - Lab Agentic AI Center',
                'meta' => [
                    ['icon' => 'calendar', 'text' => '20 Juli 2026'],
                    ['icon' => 'clock', 'text' => '09.00 - 15.00 WIB'],
                ],
                'description' => "Bootcamp bagi guru SMA/SMK/MA untuk menggunakan **tools AI** sebagai alat peraga pembelajaran di kelas dan sebagai alat bantu mengajar.\n\nKuota terbatas.",
                'benefit' => "- Sertifikat pelatihan 6 JP\n- Guru mampu memanfaatkan AI sebagai alat bantu mengajar",
                'sort_order' => 3,
            ],
        ];

        foreach ($programs as $data) {
            Program::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
