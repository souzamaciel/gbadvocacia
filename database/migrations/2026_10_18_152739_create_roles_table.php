<?php

use App\Enums\Permission;
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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('description')->nullable();
            $table->json('permissions');
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });

        $lawyer = array_values(array_filter(
            array_map(fn (Permission $permission): string => $permission->value, Permission::cases()),
            fn (string $value): bool => ! in_array($value, [Permission::UsersManage->value, Permission::RolesManage->value], true),
        ));

        $now = now();

        DB::table('roles')->insert([
            [
                'name' => 'Administrador',
                'description' => 'Acesso total, incluindo usuários e perfis de permissão.',
                'permissions' => json_encode([]),
                'is_locked' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Advogado',
                'description' => 'Atendimentos, petições e Biblioteca completa.',
                'permissions' => json_encode($lawyer),
                'is_locked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Assistente',
                'description' => 'Atendimentos e rascunhos. Só consulta a Biblioteca.',
                'permissions' => json_encode([
                    Permission::AppointmentsCreate->value,
                    Permission::PetitionsEdit->value,
                    Permission::PetitionsGenerate->value,
                    Permission::LibraryView->value,
                ]),
                'is_locked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
