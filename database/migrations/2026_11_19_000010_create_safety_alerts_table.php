<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('alarm_at');
            $table->enum('ack_type', ['aman', 'darurat'])->nullable();
            $table->enum('ack_reason', ['S', 'I', 'T'])->nullable();
            $table->string('ack_note')->nullable();
            $table->foreignId('ack_by')->nullable()->constrained('users');
            $table->timestamp('ack_at')->nullable();
            $table->boolean('muted')->default(false);
            $table->timestamps();
            $table->unique(['student_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_alerts');
    }
};
