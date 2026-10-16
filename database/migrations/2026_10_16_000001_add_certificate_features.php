<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('certificate_prefix', 60)->nullable()->unique()->after('slug');
            $table->string('certificate_front')->nullable()->after('certificate_prefix');
            $table->string('certificate_back')->nullable()->after('certificate_front');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('class_name', 50)->nullable()->after('school_level');
            $table->string('certificate_number')->nullable()->unique()->after('code');
            $table->timestamp('certificate_issued_at')->nullable()->after('status');
        });

        // Target peserta: 'sekolah' -> 'siswa' (nilai lain: guru, umum).
        DB::table('programs')->where('audience', 'sekolah')->update(['audience' => 'siswa']);

        // Kelas lama diberi prefix dari slug agar nomor sertifikat bisa dibuat.
        $used = [];
        foreach (DB::table('programs')->orderBy('id')->get(['id', 'slug']) as $program) {
            $base = trim(Str::limit(str_replace('-', '_', Str::lower($program->slug)), 50, ''), '_') ?: 'kelas';
            $prefix = $base;
            for ($i = 2; isset($used[$prefix]); $i++) {
                $prefix = $base.'_'.$i;
            }
            $used[$prefix] = true;
            DB::table('programs')->where('id', $program->id)->update(['certificate_prefix' => $prefix]);
        }

        // Status lama -> status baru. Yang sudah punya sertifikat dianggap "Terbit sertifikat",
        // yang dibatalkan dianggap "Tidak terbit sertifikat", sisanya "Mendaftar".
        DB::table('registrations')->where('progress', 'certificate_ready')->where('status', 'confirmed')
            ->update(['status' => 'certificate_issued', 'certificate_issued_at' => now()]);
        DB::table('registrations')->where('status', 'cancelled')->update(['status' => 'certificate_not_issued']);
        DB::table('registrations')->whereIn('status', ['pending', 'confirmed'])->update(['status' => 'registered']);

        // Nomor sertifikat untuk pendaftar lama (lewati bila bentrok, bisa diisi ulang admin).
        $programs = DB::table('programs')->pluck('certificate_prefix', 'id');
        $seen = [];
        foreach (DB::table('registrations')->orderBy('id')->get(['id', 'program_id', 'phone']) as $reg) {
            $digits = preg_replace('/\D+/', '', (string) $reg->phone);
            $local = str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
            $number = ($programs[$reg->program_id] ?? 'kelas').'-'.$local;
            if (isset($seen[$number])) {
                continue;
            }
            $seen[$number] = true;
            DB::table('registrations')->where('id', $reg->id)->update(['certificate_number' => $number]);
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['progress', 'certificate_link']);
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('progress', 30)->default('ongoing')->after('status');
            $table->string('certificate_link', 500)->nullable()->after('progress');
        });

        DB::table('registrations')->where('status', 'certificate_issued')
            ->update(['status' => 'confirmed', 'progress' => 'certificate_ready']);
        DB::table('registrations')->where('status', 'certificate_not_issued')->update(['status' => 'cancelled']);
        DB::table('registrations')->where('status', 'registered')->update(['status' => 'confirmed']);
        DB::table('programs')->where('audience', 'siswa')->update(['audience' => 'sekolah']);

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['certificate_number']);
            $table->dropColumn(['class_name', 'certificate_number', 'certificate_issued_at']);
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropUnique(['certificate_prefix']);
            $table->dropColumn(['certificate_prefix', 'certificate_front', 'certificate_back']);
        });
    }
};
