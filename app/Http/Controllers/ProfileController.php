<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load('role'),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());
        app(ActivityLogger::class)->log('profile.updated', 'Alterou os próprios dados (nome/e-mail).', $request->user());

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Seus dados foram atualizados.');
    }
}
