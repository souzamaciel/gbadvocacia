<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentFormController;
use App\Http\Controllers\AppointmentImportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PetitionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('', function () {
        return redirect()->route('home');
    })->name('index');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::middleware('can:appointments.create')->group(function (): void {
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointment.index');
        Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointment.create');
        Route::get('/appointments/import', [AppointmentImportController::class, 'create'])->name('appointment.import');
        Route::post('/appointments/import', [AppointmentImportController::class, 'store'])->name('appointment.import.store');
        Route::get('/appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointment.edit');
        Route::post('/appointments/drafts', [AppointmentController::class, 'storeDraft'])->name('appointment.draft.store');
        Route::put('/appointments/{appointment}/draft', [AppointmentController::class, 'updateDraft'])->name('appointment.draft.update');
        Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointment.destroy');
        Route::post('/appointments/create', [AppointmentController::class, 'store'])->name('appointment.store');
        Route::post('/appointments/print', [AppointmentController::class, 'print'])->name('appointment.print');
    });

    Route::prefix('petitions')->name('petition.')->controller(PetitionController::class)->middleware('can:petitions.edit')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{petition}/edit', 'edit')->name('edit');
        Route::put('/{petition}', 'update')->name('update');
        Route::delete('/{petition}', 'destroy')->middleware('can:petitions.delete')->name('destroy');
        Route::get('/{petition}/review', 'review')->name('review');
        Route::put('/{petition}/items/{item}', 'updateItem')->scopeBindings()->name('items.update');
        Route::get('/{petition}/generate', 'showGenerate')->middleware('can:petitions.generate')->name('generate.show');
        Route::post('/{petition}/generate', 'generate')->middleware('can:petitions.generate')->name('generate');
    });

    Route::get('/ajuda', [HelpController::class, 'index'])->name('help.index');
    Route::post('/sugestoes', [SuggestionController::class, 'store'])->middleware('throttle:10,60')->name('suggestions.store');
    Route::get('/geracoes/{token}', [GenerationController::class, 'status'])->where('token', '[A-Za-z0-9]{40}')->name('generation.status');
    Route::get('/geracoes/{token}/arquivo', [GenerationController::class, 'download'])->where('token', '[A-Za-z0-9]{40}')->name('generation.download');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/password', fn () => redirect()->route('profile.edit'))->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::prefix('settings')->name('settings.')->group(function (): void {
        Route::get('/groups', [GroupController::class, 'index'])->middleware('can:library.view')->name('groups.index');

        Route::middleware('can:groups.manage')->group(function (): void {
            Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
            Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
            Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
        });

        Route::middleware('can:library.view')->group(function (): void {
            Route::get('/topics', [TopicController::class, 'index'])->name('topics.index');
        });

        Route::middleware('can:topics.edit')->group(function (): void {
            Route::get('/topics/create', [TopicController::class, 'create'])->name('topics.create');
            Route::post('/topics', [TopicController::class, 'store'])->name('topics.store');
            Route::get('/topics/{topic}/edit', [TopicController::class, 'edit'])->name('topics.edit');
            Route::put('/topics/{topic}', [TopicController::class, 'update'])->name('topics.update');
        });

        Route::post('/topics/suggest-variables', [TopicController::class, 'suggestVariables'])->middleware('can:topics.ai')->name('topics.suggest-variables');
        Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->middleware('can:topics.delete')->name('topics.destroy');
    });

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/logs', [ActivityLogController::class, 'index'])->middleware('can:logs.view')->name('logs.index');
        Route::middleware('can:users.manage')->controller(UserController::class)->group(function (): void {
            Route::get('/users', 'index')->name('users.index');
            Route::get('/users/create', 'create')->name('users.create');
            Route::post('/users', 'store')->name('users.store');
            Route::get('/users/{user}/edit', 'edit')->name('users.edit');
            Route::put('/users/{user}', 'update')->name('users.update');
            Route::put('/users/{user}/password', 'resetPassword')->name('users.password');
            Route::put('/users/{user}/status', 'toggleStatus')->name('users.status');
        });

        Route::middleware('can:appointment_form.manage')->controller(AppointmentFormController::class)->group(function (): void {
            Route::get('/ficha', 'index')->name('form.index');
            Route::post('/ficha/assuntos', 'storeStep')->name('form.steps.store');
            Route::put('/ficha/assuntos/{step}', 'updateStep')->name('form.steps.update');
            Route::put('/ficha/assuntos/{step}/mover', 'moveStep')->name('form.steps.move');
            Route::delete('/ficha/assuntos/{step}', 'destroyStep')->name('form.steps.destroy');
            Route::post('/ficha/perguntas', 'storeQuestion')->name('form.questions.store');
            Route::put('/ficha/perguntas/{question}', 'updateQuestion')->name('form.questions.update');
            Route::put('/ficha/perguntas/{question}/mover', 'moveQuestion')->name('form.questions.move');
            Route::delete('/ficha/perguntas/{question}', 'destroyQuestion')->name('form.questions.destroy');
        });

        Route::middleware('can:roles.manage')->controller(RoleController::class)->group(function (): void {
            Route::get('/roles', 'index')->name('roles.index');
            Route::get('/roles/create', 'create')->name('roles.create');
            Route::post('/roles', 'store')->name('roles.store');
            Route::get('/roles/{role}/edit', 'edit')->name('roles.edit');
            Route::put('/roles/{role}', 'update')->name('roles.update');
            Route::delete('/roles/{role}', 'destroy')->name('roles.destroy');
        });
    });

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
