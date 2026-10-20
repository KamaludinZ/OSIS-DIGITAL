<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Jejak publikasi hasil per pemilihan (pemilihan terikat ke periode lewat periode_id). */
    public function up(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->timestamp('hasil_dipublikasikan_pada')->nullable()->after('hasil_publik');
            $table->foreignId('hasil_dipublikasikan_oleh')->nullable()->after('hasil_dipublikasikan_pada')->constrained('users')->nullOnDelete();
        });

        // Hasil yang sudah publik sebelum ada jejak: pakai waktu terakhir diperbarui.
        DB::table('elections')->where('hasil_publik', true)->update(['hasil_dipublikasikan_pada' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hasil_dipublikasikan_oleh');
            $table->dropColumn('hasil_dipublikasikan_pada');
        });
    }
};
