<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PetitionController;
use App\Http\Controllers\TopicController;
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
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointment.create');
    Route::post('/appointments/create', [AppointmentController::class, 'store'])->name('appointment.store');
    Route::post('/appointments/print', [AppointmentController::class, 'print'])->name('appointment.print');

    Route::prefix('petitions')->name('petition.')->controller(PetitionController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{petition}/edit', 'edit')->name('edit');
        Route::put('/{petition}', 'update')->name('update');
        Route::delete('/{petition}', 'destroy')->name('destroy');
        Route::get('/{petition}/review', 'review')->name('review');
        Route::put('/{petition}/items/{item}', 'updateItem')->scopeBindings()->name('items.update');
        Route::get('/{petition}/generate', 'showGenerate')->name('generate.show');
        Route::post('/{petition}/generate', 'generate')->name('generate');
        Route::get('/{petition}/download', 'download')->name('download');
    });

    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::prefix('settings')->name('settings.')->group(function (): void {
        Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
        Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
        Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
        Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');

        Route::get('/topics', [TopicController::class, 'index'])->name('topics.index');
        Route::get('/topics/create', [TopicController::class, 'create'])->name('topics.create');
        Route::post('/topics', [TopicController::class, 'store'])->name('topics.store');
        Route::post('/topics/suggest-variables', [TopicController::class, 'suggestVariables'])->name('topics.suggest-variables');
        Route::get('/topics/{topic}/edit', [TopicController::class, 'edit'])->name('topics.edit');
        Route::put('/topics/{topic}', [TopicController::class, 'update'])->name('topics.update');
        Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');
    });

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
