<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('applicable_employment_type', ['hourly', 'salaried'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('onboarding_template_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('onboarding_templates')->cascadeOnDelete();
            $table->string('title');
            $table->enum('task_type', ['form', 'document_upload', 'document_ack', 'provisioning', 'generic']);
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->foreignId('required_document_category_id')->nullable()->constrained('document_categories')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('onboarding_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_id')->constrained('employments')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('onboarding_templates');
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('onboarding_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('onboarding_workflows')->cascadeOnDelete();
            $table->foreignId('template_task_id')->nullable()->constrained('onboarding_template_tasks')->nullOnDelete();
            $table->string('title');
            $table->enum('task_type', ['form', 'document_upload', 'document_ack', 'provisioning', 'generic']);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'waived'])->default('pending');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('related_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_tasks');
        Schema::dropIfExists('onboarding_workflows');
        Schema::dropIfExists('onboarding_template_tasks');
        Schema::dropIfExists('onboarding_templates');
    }
};
