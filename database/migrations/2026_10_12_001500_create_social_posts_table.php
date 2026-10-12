<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20)->index(); // youtube | instagram | tiktok
            $table->string('judul');
            $table->string('tautan', 500);
            $table->string('thumbnail');
            $table->string('orientasi', 10)->default('potret'); // potret | lanskap
            $table->boolean('dipublikasikan')->default(true);
            $table->unsignedBigInteger('jumlah_klik')->default(0);
            $table->foreignId('pengunggah_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['dipublikasikan', 'platform', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
