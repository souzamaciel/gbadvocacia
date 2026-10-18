<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true)->after('role_id');
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });

        DB::table('users')->update([
            'role_id' => DB::table('roles')->where('is_locked', true)->value('id'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['is_active', 'must_change_password', 'last_login_at']);
        });
    }
};
