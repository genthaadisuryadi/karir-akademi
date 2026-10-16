<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Tahap setelah pendaftaran dikonfirmasi: ongoing -> completed -> certificate_ready
            $table->string('progress', 30)->default('ongoing')->after('status');
            $table->string('certificate_link', 500)->nullable()->after('progress');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['progress', 'certificate_link']);
        });
    }
};
