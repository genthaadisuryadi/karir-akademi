<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            // Asumsi field nama pemateri di database Anda bernama 'pengajar'
            $table->string('instructor_phone')->nullable()->after('pengajar');
            $table->string('instructor_certificate_front')->nullable();
            $table->string('instructor_certificate_back')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn([
                'instructor_phone', 
                'instructor_certificate_front', 
                'instructor_certificate_back'
            ]);
        });
    }
};