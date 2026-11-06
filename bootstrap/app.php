<?php

use App\Http\Middleware\CheckUserStatus;
use App\Http\Middleware\EnsurePasswordSession;
use App\Http\Middleware\EnsureUserImpersonation;
use App\Http\Middleware\SetLocale;
use App\Jobs\UpdateGptLimitForUsers;
use App\Jobs\UpdateSeasonIdForResults;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Schedule;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // commands: __DIR__.'/../routes/console.php',
        // health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web([
            EnsureUserImpersonation::class,
            CheckUserStatus::class,
            EnsurePasswordSession::class,
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'set_locale' => SetLocale::class,
        ]);
        $middleware->preventRequestForgery(except: [
            'livewire/*',
        ]);
    })
    ->withSchedule(function () {
        Schedule::command('communications:dispatch')->name('communications_dispatch')->everyMinute()->withoutOverlapping();
        Schedule::job(UpdateGptLimitForUsers::class)
            ->name('update_gpt_limit_for_users')
            ->timezone(config('app.timezone'))
            ->dailyAt('00:00');

        Schedule::job(UpdateSeasonIdForResults::class)
            ->name('update_season_id_for_results')
            ->timezone(config('app.timezone'))
            ->everyFifteenMinutes();

        Schedule::command('cache:clear')
            ->name('cache_clear')
            ->timezone(config('app.timezone'))
            ->hourlyAt(15);

        Schedule::command('app:run-calculate-league-result')
            ->name('run_league_result')
            ->timezone(config('app.timezone'))
            ->hourlyAt(0)
            ->withoutOverlapping(240);

        Schedule::command('leagues:refresh-live')
            ->name('refresh_live_leagues')
            ->timezone('Europe/Amsterdam')
            ->everyFifteenMinutes()
            ->when(function () {
                $time = now('Europe/Amsterdam');

                return $time->hour >= 9 || $time->minute === 0;
            })
            ->withoutOverlapping(240);
    })
    ->withExceptions()
    ->create();
