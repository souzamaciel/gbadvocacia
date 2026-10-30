<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Improvement ideas and problems reported by the team. Each one is e-mailed to the
     * system owner; the table keeps a copy in case an e-mail fails.
     */
    public function up(): void
    {
        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('type', 30);
            $table->string('area', 60);
            $table->string('impact', 30);
            $table->string('title');
            $table->text('description');
            $table->string('page_title')->nullable();
            $table->string('page_url', 500)->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suggestions');
    }
};
