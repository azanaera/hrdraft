<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Heterogeneous, human-readable audit trail per person: hires, rehires,
// transfers, comp changes, manual notes, document uploads, etc.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('employment_id')->nullable()->constrained('employments')->nullOnDelete();
            $table->string('event_type');
            $table->date('event_date');
            $table->text('summary');
            $table->jsonb('payload')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('visibility', ['all_hr', 'manager_and_above', 'admin_only'])->default('manager_and_above');
            $table->timestamps();

            $table->index(['person_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_events');
    }
};
