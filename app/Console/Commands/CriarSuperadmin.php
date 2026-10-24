<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CriarSuperadmin extends Command
{
    protected $signature = 'app:criar-superadmin';

    protected $description = 'Cria (ou promove) o usuário superadmin, que vê os logs e não aparece na tela de usuários';

    public function handle(): int
    {
        $email = text('E-mail do superadmin', required: true, validate: fn (string $value): ?string => Validator::make(['email' => $value], ['email' => 'email'])->fails() ? 'Informe um e-mail válido.' : null);
        $user = User::query()->where('email', $email)->first();

        if ($user && ! confirm("Já existe o usuário {$user->name} com este e-mail. Transformá-lo em superadmin?", default: false)) {
            $this->warn('Nada foi alterado.');

            return self::FAILURE;
        }

        $name = $user?->name ?? text('Nome', required: true);
        $newPassword = password('Senha (mínimo de 10 caracteres)', required: true, validate: fn (string $value): ?string => mb_strlen($value) < 10 ? 'Use pelo menos 10 caracteres.' : null);

        if (password('Repita a senha', required: true) !== $newPassword) {
            $this->error('As senhas não conferem. Nada foi alterado.');

            return self::FAILURE;
        }

        $user ??= new User(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'role_id' => Role::query()->where('is_locked', true)->value('id'),
            'password' => $newPassword,
            'is_active' => true,
            'is_super_admin' => true,
            'must_change_password' => false,
        ])->save();

        $this->info("Superadmin {$user->name} pronto. Ele não aparece na tela de Usuários e vê o menu Logs de atividade.");

        return self::SUCCESS;
    }
}
