<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('leader_id')->constrained('users');
            $table->json('scores');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
