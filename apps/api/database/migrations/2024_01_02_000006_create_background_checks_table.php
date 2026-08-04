<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('background_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_id')->constrained('employments')->cascadeOnDelete();
            $table->enum('check_type', ['background_check', 'e_verify']);
            $table->string('provider');
            $table->string('external_reference_id');
            $table->enum('status', ['pending', 'clear', 'flagged'])->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['employment_id', 'check_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('background_checks');
    }
};
