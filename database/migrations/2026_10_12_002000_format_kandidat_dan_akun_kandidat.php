<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - Pemilihan bisa berformat "ketua saja" atau "ketua + wakil".
     * - Akun berperan kandidat terhubung ke satu kandidat untuk menanggapi tanya jawab.
     */
    public function up(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->string('format_kandidat', 15)->default('ketua_wakil')->after('judul');
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->string('nama_wakil')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('candidate_id')->nullable()->after('kelas')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('candidate_id');
        });

        Schema::table('elections', function (Blueprint $table) {
            $table->dropColumn('format_kandidat');
        });
    }
};
