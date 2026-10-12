<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - aktif: admin bisa menonaktifkan akun tanpa menghapusnya (mis. masa jabatan pengurus usai).
     * - terakhir_masuk_pada: jejak masuk terakhir untuk pengawasan admin.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('aktif')->default(true)->after('candidate_id')->index();
            $table->timestamp('terakhir_masuk_pada')->nullable()->after('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['aktif', 'terakhir_masuk_pada']);
        });
    }
};
