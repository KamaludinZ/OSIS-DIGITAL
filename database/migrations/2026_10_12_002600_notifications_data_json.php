<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Filament membaca data->>'format' pada pemberitahuan basis data.
     * Di PostgreSQL operator itu hanya ada untuk kolom json, bukan text.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE json USING data::json');

            return;
        }

        Schema::table('notifications', fn (Blueprint $table) => $table->json('data')->change());
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE text USING data::text');

            return;
        }

        Schema::table('notifications', fn (Blueprint $table) => $table->text('data')->change());
    }
};
