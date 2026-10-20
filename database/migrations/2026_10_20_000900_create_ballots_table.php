<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Surat suara anonim: hanya pemilihan + kandidat yang dipilih.
     * Sengaja TANPA voter_id, tanpa waktu, dan dengan id acak (UUID v4) agar
     * urutan/waktu tidak bisa dicocokkan dengan catatan kehadiran pemilih (votes).
     */
    public function up(): void
    {
        Schema::create('ballots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->restrictOnDelete();

            // Agregasi GROUP BY candidate per pemilihan.
            $table->index(['election_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ballots');
    }
};
