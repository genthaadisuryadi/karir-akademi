<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Mengubah enum status sesuai poin 1 dan menjadikan "Mendaftar" sebagai default
            $table->enum('status', [
                'Mendaftar', 
                'Terbit sertifikat', 
                'Tidak terbit sertifikat'
            ])->default('Mendaftar')->change();
        });
    }

    public function down()
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Rollback ke status lama jika diperlukan (sesuaikan dengan status lama Anda)
            $table->enum('status', ['Menunggu', 'Diterima', 'Ditolak'])->default('Menunggu')->change();
        });
    }
};