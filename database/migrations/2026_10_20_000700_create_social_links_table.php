<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Akun media sosial resmi OSIM per periode: satu akun per platform di tiap periode. */
    public function up(): void
    {
        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->constrained('osis_periods')->restrictOnDelete();
            $table->string('platform', 20); // youtube | instagram | tiktok
            $table->string('nama_akun', 100);
            $table->string('tautan', 300);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->unsignedBigInteger('jumlah_klik')->default(0);
            $table->timestamps();

            $table->unique(['periode_id', 'platform']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE social_links ADD CONSTRAINT social_links_platform_check CHECK (platform IN ('youtube', 'instagram', 'tiktok'))");
            DB::statement("ALTER TABLE social_links ADD CONSTRAINT social_links_tautan_check CHECK (tautan LIKE 'https://%')");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('social_links');
    }
};
