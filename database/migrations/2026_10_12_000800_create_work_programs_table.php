<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_programs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->foreignId('divisi_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->boolean('unggulan')->default(false)->index();
            $table->string('status', 20)->default('rencana');
            $table->string('capaian')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_programs');
    }
};
