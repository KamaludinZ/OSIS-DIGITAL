<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * is_aktif: pengurus masih menjabat di periode berjalan (berbeda dengan `aktif` = akun bisa masuk).
     * Hanya pengurus menjabat yang boleh menanggapi saran. Akun pengurus yang sudah ada dianggap menjabat.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_aktif')->default(false)->after('aktif')->index();
        });

        DB::table('users')->where('role', 'pengurus')->where('aktif', true)->update(['is_aktif' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_aktif');
        });
    }
};
