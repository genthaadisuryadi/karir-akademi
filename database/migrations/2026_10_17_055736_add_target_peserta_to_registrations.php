<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Hapus kolom lama jika sebelumnya ada school_name dan class_name
            // $table->dropColumn(['school_name', 'class_name']); 
            
            $table->enum('target_peserta', ['Siswa SMA/SMK', 'Guru', 'Umum'])->nullable()->after('status');
            $table->string('nama_sekolah')->nullable()->after('target_peserta');
            $table->string('kelas')->nullable()->after('nama_sekolah');
            $table->string('instansi')->nullable()->after('kelas');
        });
    }
};