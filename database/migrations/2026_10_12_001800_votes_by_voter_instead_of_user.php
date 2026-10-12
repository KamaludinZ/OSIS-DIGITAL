<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Suara kini dicatat per pemilih (kode pilih), bukan per akun login.
     * Suara lama berbasis akun tidak bisa dipetakan, jadi dikosongkan.
     */
    public function up(): void
    {
        DB::table('votes')->delete();

        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique(['election_id', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->foreignId('voter_id')->after('election_id')->constrained()->cascadeOnDelete();
            // Satu pemilih hanya boleh memilih sekali per periode pemilihan.
            $table->unique(['election_id', 'voter_id']);
        });

        Schema::table('candidate_questions', function (Blueprint $table) {
            $table->foreignId('voter_id')->nullable()->after('penanya_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('candidate_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voter_id');
        });

        DB::table('votes')->delete();

        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique(['election_id', 'voter_id']);
            $table->dropConstrainedForeignId('voter_id');
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->foreignId('user_id')->after('election_id')->constrained()->cascadeOnDelete();
            $table->unique(['election_id', 'user_id']);
        });
    }
};
