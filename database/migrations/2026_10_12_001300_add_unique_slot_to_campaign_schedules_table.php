<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Satu tempat tidak boleh dipakai dua kampanye pada tanggal & jam yang sama. */
    public function up(): void
    {
        Schema::table('campaign_schedules', function (Blueprint $table) {
            $table->unique(['tanggal', 'waktu', 'tempat'], 'campaign_schedules_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_schedules', function (Blueprint $table) {
            $table->dropUnique('campaign_schedules_slot_unique');
        });
    }
};
