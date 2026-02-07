<?php

use App\Http\Middleware\SetLocale;
use App\Jobs\CalculateLeagueResult;
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
        Schedule::job(\App\Jobs\UpdateGptLimitForUsers::class)
            ->name('update_gpt_limit_for_users')
            ->timezone(config('app.timezone'))
            ->dailyAt('00:00');

        Schedule::command('backup:run --only-db')
            ->name('backup_run_only_db')
            ->timezone(config('app.timezone'))
            ->wednesdays()
            ->saturdays()
            ->at('01:30');

        Schedule::command('backup:run')
            ->name('backup_run')
            ->timezone(config('app.timezone'))
            ->mondays()
            ->at('02:30');

        Schedule::job(\App\Jobs\UploadBackupToCloudflare::class)
            ->name('upload_backup_to_cloudflare')
            ->timezone(config('app.timezone'))
            ->dailyAt('04:30');

        Schedule::command('optimize:clear')
            ->name('optimize_clear')
            ->timezone(config('app.timezone'))
            ->hourlyAt(15);

        Schedule::command('telescope:prune --hours=72')
            ->name('telescope_prune')
            ->dailyAt('05:30');

        Schedule::command('app:run-calculate-league-result')
            ->name('run_league_result')
            ->hourlyAt(0);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //Integration::handles($exceptions);
    })->create();
