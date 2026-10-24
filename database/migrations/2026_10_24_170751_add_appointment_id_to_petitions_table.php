<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A petition built from an appointment keeps the link to it (the appointment's ficha
     * fills the review and the generation).
     */
    public function up(): void
    {
        Schema::table('petitions', function (Blueprint $table) {
            $table->foreignId('appointment_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('petitions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('appointment_id');
        });
    }
};
