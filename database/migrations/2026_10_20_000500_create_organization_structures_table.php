<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Satu gambar bagan struktur organisasi per periode, dengan rasio tampilan 4:3 atau 3:4. */
    public function up(): void
    {
        Schema::create('organization_structures', function (Blueprint $table) {
            $table->id();
            // Unik: tiap periode hanya punya satu bagan.
            $table->foreignId('periode_id')->unique()->constrained('osis_periods')->restrictOnDelete();
            $table->string('gambar'); // path di disk public
            $table->string('rasio', 3)->default('4:3');
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE organization_structures ADD CONSTRAINT organization_structures_rasio_check CHECK (rasio IN ('4:3', '3:4'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_structures');
    }
};
