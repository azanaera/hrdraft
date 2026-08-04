<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // State-level minimum wage for the location's jurisdiction —
            // validated against hourly compensation in CompensationService.
            $table->decimal('minimum_wage', 6, 2)->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('minimum_wage');
        });
    }
};
