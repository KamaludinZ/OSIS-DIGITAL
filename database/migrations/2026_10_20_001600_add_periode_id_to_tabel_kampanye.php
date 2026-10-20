<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Materi, jadwal kampanye, dan tanya jawab ikut tercatat per periode (dari kandidatnya),
     * sehingga arsip kampanye tiap kepengurusan bisa ditelusuri langsung.
     * Status moderasi tanya jawab tetap kolom `ditampilkan` (tampil/disembunyikan) + `jawaban`.
     */
    private const TABEL = ['campaign_materials', 'campaign_schedules', 'candidate_questions'];

    public function up(): void
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->foreignId('periode_id')->nullable()->after('candidate_id')->constrained('osis_periods')->restrictOnDelete();
            });

            DB::table($tabel)->whereNull('periode_id')->update([
                'periode_id' => DB::table('candidates')->select('periode_id')->whereColumn('candidates.id', $tabel.'.candidate_id')->limit(1),
            ]);
        }
    }

    public function down(): void
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, fn (Blueprint $table) => $table->dropConstrainedForeignId('periode_id'));
        }
    }
};
