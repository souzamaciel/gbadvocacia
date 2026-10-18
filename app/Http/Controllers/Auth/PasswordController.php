<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->password = Hash::make($request->string('password')->toString());
        $user->must_change_password = false;
        $user->save();

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Senha atualizada com sucesso!');
    }
}
