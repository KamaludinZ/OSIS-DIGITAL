<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Batasi tipe materi kampanye; poster wajib punya url_konten (PostgreSQL). */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE campaign_materials ADD CONSTRAINT campaign_materials_tipe_check CHECK (tipe IN ('poster', 'video', 'janji'))");
        DB::statement("ALTER TABLE campaign_materials ADD CONSTRAINT campaign_materials_url_check CHECK (tipe IN ('janji', 'video') OR url_konten IS NOT NULL)");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE campaign_materials DROP CONSTRAINT IF EXISTS campaign_materials_url_check');
        DB::statement('ALTER TABLE campaign_materials DROP CONSTRAINT IF EXISTS campaign_materials_tipe_check');
    }
};
