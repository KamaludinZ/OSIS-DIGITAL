<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->restrictOnDelete();
            // Kode bukti untuk pemilih; tidak membuka pilihan.
            $table->string('kode_bukti', 20)->unique();
            $table->timestamp('created_at')->useCurrent();

            // Satu siswa hanya boleh memilih sekali per periode pemilihan.
            $table->unique(['election_id', 'user_id']);
            $table->index(['election_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
