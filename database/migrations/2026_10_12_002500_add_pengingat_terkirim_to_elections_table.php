<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Kunci pengingat pemilihan yang sudah dikirim ke akun (mis. "buka@2026-10-20 07:00"). */
    public function up(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->json('pengingat_terkirim')->nullable()->after('hasil_publik');
        });
    }

    public function down(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->dropColumn('pengingat_terkirim');
        });
    }
};
