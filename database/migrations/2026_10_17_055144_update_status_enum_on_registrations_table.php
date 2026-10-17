<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Amankan data lama: Ubah status yang tidak valid (seperti 'Menunggu') menjadi 'registered'
        DB::table('registrations')
            ->whereNotIn('status', ['registered', 'certificate_issued', 'certificate_not_issued'])
            ->update(['status' => 'registered']);

        // 2. Ubah struktur kolom menggunakan key yang sesuai dengan model Registration.php
        Schema::table('registrations', function (Blueprint $table) {
            $table->enum('status', [
                'registered', 
                'certificate_issued', 
                'certificate_not_issued'
            ])->default('registered')->change();
        });
    }

    public function down()
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->enum('status', ['Menunggu', 'Diterima', 'Ditolak'])->default('Menunggu')->change();
        });
    }
};