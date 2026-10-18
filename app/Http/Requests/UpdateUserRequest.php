<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $target */
        $target = $this->route('user');

        return ($this->user()?->can('users.manage') ?? false) && $this->user()->canManageUser($target);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var User $target */
                $target = $this->route('user');
                $role = Role::find($this->integer('role_id'));

                if ($role === null || $role->id === $target->role_id) {
                    return;
                }

                if ($target->is($this->user())) {
                    $validator->errors()->add('role_id', 'Você não pode alterar o seu próprio perfil.');
                } elseif ($role->isAdministrator() && ! $this->user()->isAdministrator()) {
                    $validator->errors()->add('role_id', 'Só administradores podem dar o perfil Administrador.');
                } elseif ($target->isAdministrator() && $target->is_active && ! $target->hasAnotherActiveAdministrator()) {
                    $validator->errors()->add('role_id', 'É preciso manter pelo menos 1 administrador ativo.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do usuário.',
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'email.required' => 'Informe o e-mail do usuário.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve ter no máximo 255 caracteres.',
            'email.unique' => 'Este e-mail já é usado por outro usuário.',
            'role_id.required' => 'Escolha o perfil de permissão.',
            'role_id.exists' => 'Escolha um perfil de permissão válido.',
        ];
    }
}
