<?php

use App\Http\Middleware\SetLocale;
use App\Jobs\CalculateLeagueResult;
use App\Jobs\UpdateSeasonIdForResults;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use \Illuminate\Support\Facades\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
    //commands: __DIR__.'/../routes/console.php',
    //health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web([
            \App\Http\Middleware\CheckUserStatus::class
        ]);
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'set_locale' => SetLocale::class,
        ]);
        $middleware->preventRequestForgery(except: [
            'livewire/*',
        ]);
    })
    ->withSchedule(function () {
        Schedule::command('communications:dispatch')->name('communications_dispatch')->everyMinute()->withoutOverlapping();
        Schedule::job(\App\Jobs\UpdateGptLimitForUsers::class)
            ->name('update_gpt_limit_for_users')
            ->timezone(config('app.timezone'))
            ->dailyAt('00:00');

        Schedule::job(\App\Jobs\UpdateSeasonIdForResults::class)
            ->name('update_season_id_for_results')
            ->timezone(config('app.timezone'))
            ->everyFifteenMinutes();

        Schedule::command('cache:clear')
            ->name('cache_clear')
            ->timezone(config('app.timezone'))
            ->hourlyAt(15);

        Schedule::command('app:run-calculate-league-result')
            ->name('run_league_result')
            ->hourlyAt(0);
    })
    ->withExceptions()
    ->create();
