<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('order');
            $table->boolean('is_terminal')->default(false);
            $table->timestamps();
        });

        Schema::create('job_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('location_id')->constrained('locations');
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->enum('status', ['draft', 'open', 'on_hold', 'filled', 'closed'])->default('draft');
            $table->foreignId('hiring_manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('target_pay_range_min', 12, 2)->nullable();
            $table->decimal('target_pay_range_max', 12, 2)->nullable();
            $table->enum('employment_type', ['hourly', 'salaried']);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Candidates are intentionally NOT employees. linked_person_id is set
        // on hire, and also lets the hire flow detect a candidate who is
        // actually a former employee applying again (rehire-aware).
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('resume_document_path')->nullable();
            $table->string('source')->nullable();
            $table->foreignId('linked_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->timestamps();

            $table->index(['email']);
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('job_requisitions')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('current_stage_id')->constrained('pipeline_stages');
            $table->enum('status', ['active', 'rejected', 'withdrawn', 'hired'])->default('active');
            $table->timestamp('applied_at')->useCurrent();
            $table->string('rejected_reason')->nullable();
            $table->foreignId('hired_employment_id')->nullable()->constrained('employments')->nullOnDelete();
            $table->timestamps();

            $table->unique(['requisition_id', 'candidate_id']);
        });

        Schema::create('application_stage_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('pipeline_stages');
            $table->timestamp('entered_at');
            $table->timestamp('exited_at')->nullable();
            $table->foreignId('moved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('interview_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('interviewer_user_id')->constrained('users');
            $table->timestamp('scheduled_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_notes');
        Schema::dropIfExists('application_stage_history');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('job_requisitions');
        Schema::dropIfExists('pipeline_stages');
    }
};
