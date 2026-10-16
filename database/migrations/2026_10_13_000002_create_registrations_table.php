<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('school_name');
            $table->string('school_level')->nullable(); // SMA / SMK / MA
            $table->string('city');
            $table->string('position')->nullable();      // jabatan / mapel (guru)
            $table->unsignedSmallInteger('student_count')->nullable(); // program sekolah
            $table->string('preferred_schedule')->nullable();
            $table->text('motivation')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['program_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
