<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Anonimitas suara: pilihan dipindah dari votes ke ballots (tanpa identitas),
     * lalu kolom votes.candidate_id dihapus. votes kini hanya catatan kehadiran
     * (siapa sudah memilih + kode bukti), tidak lagi memuat apa yang dipilih.
     */
    public function up(): void
    {
        DB::table('votes')->whereNotNull('candidate_id')->orderBy('id')
            ->select(['election_id', 'candidate_id'])
            ->chunk(500, function ($baris) {
                DB::table('ballots')->insert($baris->map(fn ($v) => [
                    'id' => (string) Str::uuid(),
                    'election_id' => $v->election_id,
                    'candidate_id' => $v->candidate_id,
                ])->shuffle()->all()); // urutan diacak: tidak mengikuti urutan kehadiran
            });

        Schema::table('votes', function (Blueprint $table) {
            $table->dropIndex(['election_id', 'candidate_id']);
        });
        Schema::table('votes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('candidate_id');
        });
    }

    public function down(): void
    {
        // Hubungan pemilih-pilihan sengaja tidak bisa dipulihkan.
        Schema::table('votes', function (Blueprint $table) {
            $table->foreignId('candidate_id')->nullable()->after('voter_id')->constrained()->nullOnDelete();
            $table->index(['election_id', 'candidate_id']);
        });
    }
};
