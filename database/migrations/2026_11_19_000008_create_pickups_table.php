<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['belajar', 'siap', 'dijemput'])->default('belajar');
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('picked_at')->nullable();
            $table->string('picked_by')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->boolean('otw')->default(false);
            $table->timestamps();
            $table->unique(['student_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickups');
    }
};
