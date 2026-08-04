<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// One row per location/department/position/manager period for an employment.
// A transfer = insert a new row with a new effective_start_date and close the
// prior row's effective_end_date — never an in-place field update.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_id')->constrained('employments')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('location_id')->constrained('locations');
            $table->foreignId('position_id')->constrained('positions');
            $table->foreignId('manager_employment_id')->nullable()->constrained('employments')->nullOnDelete();
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->boolean('is_current')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employment_id', 'is_current']);
        });

        // Only one "current" assignment per employment at a time.
        DB::statement(
            'CREATE UNIQUE INDEX assignments_one_current_per_employment '.
            'ON assignments (employment_id) WHERE is_current = true'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
