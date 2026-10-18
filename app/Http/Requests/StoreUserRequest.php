<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? false;
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
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'password' => ['required', 'string', Password::min(8)],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $role = Role::find($this->integer('role_id'));

                if ($role?->isAdministrator() && ! $this->user()->isAdministrator()) {
                    $validator->errors()->add('role_id', 'Só administradores podem dar o perfil Administrador.');
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
            'password.required' => 'Defina a senha provisória.',
            'password.min' => 'A senha provisória deve ter pelo menos 8 caracteres.',
        ];
    }
}
