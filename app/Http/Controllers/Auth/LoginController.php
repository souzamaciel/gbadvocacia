<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            if (! $request->user()->is_active) {
                app(ActivityLogger::class)->log('auth.blocked', 'Tentou entrar com o acesso desativado.');
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Seu acesso está desativado. Fale com o administrador do sistema.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            $request->user()->forceFill(['last_login_at' => now()])->save();
            app(ActivityLogger::class)->log('auth.login', 'Entrou no sistema.');

            return redirect()->intended(route('home'));
        }

        app(ActivityLogger::class)->log('auth.failed', 'Senha ou e-mail incorretos.', properties: ['E-mail informado' => $request->string('email')->toString()]);

        return back()->withErrors([
            'email' => 'As credenciais informadas estão incorretas.',
        ])->onlyInput('email');
    }

    public function destroy(Request $request): RedirectResponse
    {
        app(ActivityLogger::class)->log('auth.logout', 'Saiu do sistema.');
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
