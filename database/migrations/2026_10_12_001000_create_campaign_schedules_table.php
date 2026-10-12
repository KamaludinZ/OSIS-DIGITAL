<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('waktu');
            $table->string('tempat');
            $table->timestamps();

            $table->index(['candidate_id', 'tanggal', 'waktu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_schedules');
    }
};
