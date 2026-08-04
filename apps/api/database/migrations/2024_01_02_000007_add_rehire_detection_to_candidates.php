<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('phone');
            // Auto-detected name+DOB match, surfaced to HR for confirmation —
            // distinct from linked_person_id, which is a confirmed link
            // (either an exact email match, or HR-confirmed from this field).
            $table->foreignId('possible_former_employee_person_id')->nullable()
                ->after('linked_person_id')->constrained('people')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('possible_former_employee_person_id');
            $table->dropColumn('date_of_birth');
        });
    }
};
