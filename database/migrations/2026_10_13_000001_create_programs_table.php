<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('short_title');
            $table->string('audience'); // 'sekolah' (siswa SMA/SMK) | 'guru'
            $table->string('poster')->nullable();
            $table->string('price_label')->default('GRATIS');
            $table->unsignedSmallInteger('jp')->default(0);
            $table->string('date_label');
            $table->string('time_label')->nullable();
            $table->string('location')->nullable();
            $table->json('meta')->nullable(); // baris info tambahan di kartu
            $table->unsignedSmallInteger('min_participants')->nullable();
            $table->unsignedInteger('quota')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
