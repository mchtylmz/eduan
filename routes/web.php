<?php

use App\Mail\EmailVerificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/* ***************************************** */
// backend login
/*
Route::middleware(['guest', 'set_locale'])
    ->controller(\App\Http\Controllers\Backend\Auth\LoginController::class)
    ->prefix(config('backend.prefix'))
    ->group(function () {
        Route::get('/login', 'index')->name('login');
        Route::post('/login', 'store')->name('login');
    });
*/

// frontend login
Route::middleware(['guest', 'set_locale'])
    ->controller(\App\Http\Controllers\Frontend\AuthController::class)
    ->prefix('auth')
    ->group(function () {
        Route::get('login', 'login')->name('login');
    });

Route::name('frontend.')
    ->middleware(['web', 'set_locale'])
    ->group(base_path('routes/frontend.php'));

// backend
Route::name('admin.')
    ->middleware(['auth', 'set_locale', 'can:dashboard:access'])
    ->prefix(config('backend.prefix'))
    ->group(base_path('routes/backend.php'));

\Livewire\Livewire::setUpdateRoute(function ($handle) {
    return Route::post('livewire/update', $handle);
});
