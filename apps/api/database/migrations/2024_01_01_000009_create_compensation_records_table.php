<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Effective-dated pay history. The GIST exclusion constraint below guarantees
// at the database level that no employment can have two overlapping active
// rate periods — application code (CompensationService) closes the prior
// record's end_date before inserting a new one, but the constraint is the
// real backstop.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compensation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_id')->constrained('employments')->cascadeOnDelete();
            $table->enum('pay_type', ['hourly', 'salary']);
            $table->decimal('rate_amount', 12, 2);
            $table->enum('pay_frequency', ['weekly', 'biweekly', 'semimonthly', 'monthly', 'annual']);
            $table->char('currency', 3)->default('USD');
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->enum('reason', ['new_hire', 'raise', 'promotion', 'transfer', 'rehire', 'adjustment', 'correction']);
            $table->foreignId('related_assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employment_id', 'effective_date']);
        });

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        DB::statement(<<<'SQL'
            ALTER TABLE compensation_records
            ADD CONSTRAINT compensation_records_no_overlap
            EXCLUDE USING gist (
                employment_id WITH =,
                daterange(effective_date, end_date, '[]') WITH &&
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('compensation_records');
    }
};
