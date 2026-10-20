<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rekap agregat: jumlah suara per kandidat per pemilihan, ditambah dalam transaksi
     * yang sama dengan surat suara anonim. Tanpa waktu, agar perubahan hitungan tidak
     * bisa dicocokkan dengan waktu kehadiran pemilih. Sumber kebenaran tetap ballots.
     */
    public function up(): void
    {
        Schema::create('vote_tallies', function (Blueprint $table) {
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('jumlah')->default(0);

            $table->primary(['election_id', 'candidate_id']);
        });

        // Isi dari surat suara yang sudah ada.
        DB::table('vote_tallies')->insertUsing(
            ['election_id', 'candidate_id', 'jumlah'],
            DB::table('ballots')->selectRaw('election_id, candidate_id, count(*)')->groupBy('election_id', 'candidate_id'),
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_tallies');
    }
};
