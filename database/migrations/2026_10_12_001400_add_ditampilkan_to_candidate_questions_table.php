<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Moderasi: panitia bisa menyembunyikan pertanyaan tanpa menghapusnya. */
    public function up(): void
    {
        Schema::table('candidate_questions', function (Blueprint $table) {
            $table->boolean('ditampilkan')->default(true)->after('dijawab_pada');
            $table->index(['candidate_id', 'ditampilkan']);
        });
    }

    public function down(): void
    {
        Schema::table('candidate_questions', function (Blueprint $table) {
            $table->dropIndex(['candidate_id', 'ditampilkan']);
            $table->dropColumn('ditampilkan');
        });
    }
};
