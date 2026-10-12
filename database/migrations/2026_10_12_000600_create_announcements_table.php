<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('kategori', 50)->default('Umum');
            $table->string('ringkasan', 300)->nullable();
            $table->text('isi');
            // Nama penulis yang ditampilkan (mis. "Panitia Pemilihan"); akun pembuat di penulis_id.
            $table->string('penulis_label')->nullable();
            $table->foreignId('penulis_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('dipublikasikan')->default(false);
            $table->timestamp('dipublikasikan_pada')->nullable();
            $table->timestamps();

            $table->index(['dipublikasikan', 'dipublikasikan_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
