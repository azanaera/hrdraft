<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One row per employment stint. A rehire is a brand-new row with the same
// person_id — never an update to a prior row — so employment history is
// never destroyed or overwritten.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('employee_number')->unique();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->string('termination_reason')->nullable();
            $table->boolean('rehire_eligible')->default(true);
            $table->enum('employment_status', ['active', 'on_leave', 'terminated'])->default('active');
            $table->enum('employment_type', ['hourly', 'salaried']);
            $table->timestamps();

            $table->index(['person_id']);
            $table->index(['employment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employments');
    }
};
