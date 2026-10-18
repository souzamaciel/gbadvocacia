<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class ResetUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $target */
        $target = $this->route('user');

        return ($this->user()?->can('users.manage') ?? false) && $this->user()->canManageUser($target);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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
                if ($this->route('user')->is($this->user())) {
                    $validator->errors()->add('password', 'Para trocar a sua própria senha, use “Meu perfil”.');
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
            'password.required' => 'Defina a senha provisória.',
            'password.min' => 'A senha provisória deve ter pelo menos 8 caracteres.',
        ];
    }
}
