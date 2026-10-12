<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Penanda pemberitahuan pengumuman sudah dikirim, agar tidak terkirim dua kali. */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->timestamp('diberitahukan_pada')->nullable()->after('dipublikasikan_pada');
        });

        // Pengumuman yang sudah tayang sebelum fitur ini ada tidak diberitahukan ulang.
        DB::table('announcements')->where('dipublikasikan', true)
            ->where(fn ($q) => $q->whereNull('dipublikasikan_pada')->orWhere('dipublikasikan_pada', '<=', now()))
            ->update(['diberitahukan_pada' => now()]);
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('diberitahukan_pada');
        });
    }
};
