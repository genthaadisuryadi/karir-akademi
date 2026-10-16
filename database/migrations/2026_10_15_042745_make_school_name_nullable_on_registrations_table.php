<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('school_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('registrations')
            ->whereNull('school_name')
            ->update(['school_name' => 'Perorangan']);

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('school_name')->nullable(false)->change();
        });
    }
};
