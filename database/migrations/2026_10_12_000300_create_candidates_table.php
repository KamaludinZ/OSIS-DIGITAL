<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('nomor_urut');
            $table->string('nama_ketua');
            $table->string('kelas_ketua', 50)->nullable();
            $table->string('nama_wakil');
            $table->string('kelas_wakil', 50)->nullable();
            $table->string('slogan')->nullable();
            $table->text('latar_belakang')->nullable();
            $table->text('visi')->nullable();
            $table->json('misi')->nullable();
            $table->string('foto_url')->nullable();
            $table->timestamps();

            $table->unique(['election_id', 'nomor_urut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
