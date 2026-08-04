<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Deliberately has NO raw account/routing number columns — only a token
// returned by a tokenizing banking provider (Plaid/Stripe in production,
// FakeBankingProvider locally). The schema itself can't regress into
// storing real banking data later.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_banking_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_id')->constrained('employments')->cascadeOnDelete();
            $table->string('provider');
            $table->string('external_token');
            $table->string('account_last_four', 4);
            $table->enum('account_type', ['checking', 'savings']);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique('employment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_banking_info');
    }
};
