<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The pedidos with their estimated amounts, checked by the lawyer before the Word is
     * generated. The signature tells when the topics or the ficha changed afterwards.
     */
    public function up(): void
    {
        Schema::table('petitions', function (Blueprint $table) {
            $table->json('pedidos')->nullable()->after('status');
            $table->string('pedidos_signature', 32)->nullable()->after('pedidos');
            $table->timestamp('pedidos_confirmed_at')->nullable()->after('pedidos_signature');
        });
    }

    public function down(): void
    {
        Schema::table('petitions', function (Blueprint $table) {
            $table->dropColumn(['pedidos', 'pedidos_signature', 'pedidos_confirmed_at']);
        });
    }
};
