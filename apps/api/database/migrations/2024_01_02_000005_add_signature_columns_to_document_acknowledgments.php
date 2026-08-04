<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_acknowledgments', function (Blueprint $table) {
            $table->string('signature_provider')->nullable()->after('signature_data');
            $table->string('signature_envelope_id')->nullable()->after('signature_provider');
            $table->enum('signature_status', ['sent', 'signed', 'declined'])->default('signed')->after('signature_envelope_id');
        });
    }

    public function down(): void
    {
        Schema::table('document_acknowledgments', function (Blueprint $table) {
            $table->dropColumn(['signature_provider', 'signature_envelope_id', 'signature_status']);
        });
    }
};
