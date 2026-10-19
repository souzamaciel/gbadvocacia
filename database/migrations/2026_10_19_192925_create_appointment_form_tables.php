<?php

use Database\Seeders\AppointmentFormSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointment_form_steps', function (Blueprint $table) {
            $table->id();
            $table->string('title', 80);
            $table->string('description')->nullable();
            $table->string('icon', 40)->default('clipboard-list');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('appointment_form_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_form_step_id')->constrained()->cascadeOnDelete();
            $table->string('key', 80)->unique();
            $table->string('type', 30);
            $table->text('label');
            $table->string('help', 500)->nullable();
            $table->string('placeholder', 120)->nullable();
            $table->json('options')->nullable();
            $table->json('show_if')->nullable();
            $table->string('width', 20)->default('full');
            $table->boolean('required')->default(false);
            $table->boolean('send_to_ai')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        (new AppointmentFormSeeder)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_form_questions');
        Schema::dropIfExists('appointment_form_steps');
    }
};
