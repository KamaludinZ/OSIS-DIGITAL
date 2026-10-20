<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengumuman per periode kepengurusan (jadwal tayang tetap memakai dipublikasikan_pada).
     * Pengumuman lama masuk periode aktif; tanpa periode aktif tetap tanpa periode.
     */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->foreignId('periode_id')->nullable()->after('id')->constrained('osis_periods')->restrictOnDelete();
        });

        if ($aktif = DB::table('osis_periods')->where('status', 'aktif')->value('id')) {
            DB::table('announcements')->whereNull('periode_id')->update(['periode_id' => $aktif]);
        }
    }

    public function down(): void
    {
        Schema::table('announcements', fn (Blueprint $table) => $table->dropConstrainedForeignId('periode_id'));
    }
};
