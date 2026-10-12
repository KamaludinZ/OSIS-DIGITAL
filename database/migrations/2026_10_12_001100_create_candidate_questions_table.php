<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('penanya_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('pertanyaan');
            $table->text('jawaban')->nullable();
            $table->timestamp('dijawab_pada')->nullable();
            $table->timestamps();

            $table->index(['candidate_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_questions');
    }
};
