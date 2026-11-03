<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * The suggestions channel was removed: drops its table, the screenshots sent with
     * the suggestions and their entries in the activity log.
     */
    public function up(): void
    {
        Schema::dropIfExists('suggestions');
        Storage::disk('local')->deleteDirectory('sugestoes');
        DB::table('activity_logs')->where('event', 'suggestion.sent')->delete();
    }

    public function down(): void
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
};
