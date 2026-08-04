<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_off_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('applies_to', ['hourly', 'salaried', 'all'])->default('all');
            $table->enum('accrual_method', ['fixed_annual', 'per_pay_period', 'none'])->default('per_pay_period');
            $table->decimal('accrual_rate', 8, 2)->default(0);
            $table->decimal('max_balance', 8, 2)->nullable();
            $table->string('carryover_rule')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('time_off_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_id')->constrained('employments')->cascadeOnDelete();
            $table->foreignId('policy_id')->constrained('time_off_policies');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('hours_requested', 8, 2);
            $table->enum('status', ['pending', 'approved', 'denied', 'cancelled'])->default('pending');
            $table->timestamp('requested_at')->useCurrent();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();

            $table->index(['employment_id', 'status']);
        });

        Schema::create('time_off_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_id')->constrained('employments')->cascadeOnDelete();
            $table->foreignId('policy_id')->constrained('time_off_policies');
            $table->enum('entry_type', ['accrual', 'request_deduction', 'adjustment', 'carryover']);
            $table->decimal('hours', 8, 2);
            $table->date('effective_date');
            $table->foreignId('related_request_id')->nullable()->constrained('time_off_requests')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employment_id', 'policy_id']);
        });

        Schema::create('time_off_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_id')->constrained('employments')->cascadeOnDelete();
            $table->foreignId('policy_id')->constrained('time_off_policies');
            $table->decimal('balance_hours', 8, 2)->default(0);
            $table->date('as_of_date');
            $table->timestamps();

            $table->unique(['employment_id', 'policy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_off_balances');
        Schema::dropIfExists('time_off_ledger_entries');
        Schema::dropIfExists('time_off_requests');
        Schema::dropIfExists('time_off_policies');
    }
};
