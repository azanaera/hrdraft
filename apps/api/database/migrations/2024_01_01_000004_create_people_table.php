<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A `person` is the human being and persists across every hire/rehire.
// Employment stints live in `employments`, never here — this table is never
// "updated on termination," only ever gains new related employments.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('person_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('phone')->nullable();
            $table->text('ssn_encrypted')->nullable();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
