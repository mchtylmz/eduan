<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;
use \Illuminate\Support\Facades\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
    //commands: __DIR__.'/../routes/console.php',
    //health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'set_locale' => SetLocale::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'livewire/*',
        ]);
    })
    ->withSchedule(function () {
        Schedule::command('backup:run --only-db')
            ->timezone(config('app.timezone'))
            ->wednesdays()
            ->saturdays()
            ->at('00:15');

        Schedule::command('backup:run')
            ->timezone(config('app.timezone'))
            ->mondays()
            ->at('02:00');

        Schedule::job(\App\Jobs\UploadBackupToCloudflare::class)
            ->timezone(config('app.timezone'))
            ->mondays()
            ->saturdays()
            ->at('04:00');

        Schedule::command('optimize:clear')
            ->timezone(config('app.timezone'))
            ->hourlyAt(15);

        Schedule::command('telescope:prune --hours=72')
            ->dailyAt('05:30');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //Integration::handles($exceptions);
    })->create();
