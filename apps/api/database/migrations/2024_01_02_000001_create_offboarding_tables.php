<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mirrors onboarding_* exactly, in reverse intent — see
// 2024_01_01_000012_create_onboarding_tables.php.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offboarding_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('offboarding_template_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('offboarding_templates')->cascadeOnDelete();
            $table->string('title');
            $table->enum('task_type', ['equipment_return', 'access_revocation', 'exit_interview', 'final_payout', 'generic']);
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });

        Schema::create('offboarding_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_id')->constrained('employments')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('offboarding_templates');
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('offboarding_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('offboarding_workflows')->cascadeOnDelete();
            $table->foreignId('template_task_id')->nullable()->constrained('offboarding_template_tasks')->nullOnDelete();
            $table->string('title');
            $table->enum('task_type', ['equipment_return', 'access_revocation', 'exit_interview', 'final_payout', 'generic']);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'waived'])->default('pending');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offboarding_tasks');
        Schema::dropIfExists('offboarding_workflows');
        Schema::dropIfExists('offboarding_template_tasks');
        Schema::dropIfExists('offboarding_templates');
    }
};
