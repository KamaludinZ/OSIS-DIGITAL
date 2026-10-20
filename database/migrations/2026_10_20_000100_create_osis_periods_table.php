<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Periode kepengurusan OSIM (contoh: 2026/2027).
     * status: disiapkan -> aktif -> arsip. Hanya boleh ada satu periode aktif.
     */
    public function up(): void
    {
        Schema::create('osis_periods', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 9)->unique();
            $table->date('mulai');
            $table->date('selesai');
            $table->string('status', 12)->default('disiapkan')->index();
            $table->timestamps();
        });

        // Satu periode aktif dijaga basis data (indeks unik parsial; PostgreSQL & SQLite).
        DB::statement("CREATE UNIQUE INDEX osis_periods_satu_aktif ON osis_periods (status) WHERE status = 'aktif'");

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE osis_periods ADD CONSTRAINT osis_periods_status_check CHECK (status IN ('disiapkan', 'aktif', 'arsip'))");
            DB::statement('ALTER TABLE osis_periods ADD CONSTRAINT osis_periods_rentang_check CHECK (selesai > mulai)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('osis_periods');
    }
};
