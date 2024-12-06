<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/deneme', function () {
    foreach (Storage::files(config('app.name')) as $file) {
        if (!str_ends_with($file, '.zip')) continue;

        $filename = str_replace(
            [config('app.name'), '.zip'], '', $file
        );

        $uploadedFile = Illuminate\Support\Facades\Storage::disk('s3')
            ->putFileAs(date('Y-m'), storage_path('app/private/' . $file), \Illuminate\Support\Str::slug($filename));

        if ($uploadedFile) {
            unlink(storage_path('app/private/' . $file));
        }

        break;
    }
});

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
