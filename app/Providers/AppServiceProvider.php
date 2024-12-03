<?php

namespace App\Providers;

use App\Enums\RoleTypeEnum;
use App\Models\Language;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;
use Pharaonic\Laravel\Settings\Models\Settings;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RedirectIfAuthenticated::redirectUsing(function () {
            if (request()->user()->can('dashboard:access')) {
                return route('admin.home.index');
            }

            return route('frontend.profile');
        });

        if (!app()->environment('production')) {
            cache()->flush();
        }

        if (app()->environment('production')) {
            Facades\URL::forceScheme('https');
            request()->server->set('HTTPS', 'on');
        }

        Paginator::useBootstrapFive();

        $this->overrideTimezone();
        $this->overrideMailConfig();
        $this->viewComposer();
        $this->registerMacros();
    }

    public function viewComposer(): void
    {
        Facades\View::composer('*', function (View $view) {

        });
    }

    public function registerMacros(): void
    {

    }

    protected function overrideMailConfig(): void
    {
        Facades\Config::set('mail.mailers.smtp.host', settings()->mailHost);
        Facades\Config::set('mail.mailers.smtp.port', settings()->mailPort);
        Facades\Config::set('mail.mailers.smtp.username', settings()->mailUsername);
        Facades\Config::set('mail.mailers.smtp.password', settings()->mailPassword);
        Facades\Config::set('mail.mailers.smtp.encryption', settings()->mailEncryptionType);
        Facades\Config::set('mail.from.address', settings()->mailFromEmail);
        Facades\Config::set('mail.from.name', settings()->mailFromName);
    }

    protected function overrideTimezone(): void
    {
        Facades\Config::set('app.timezone', settings()->timezone ?? env('APP_TIMEZONE'));

        setlocale(LC_TIME, app()->getLocale().'.utf8');
        Carbon::setLocale(app()->getLocale());
        date_default_timezone_set(config('app.timezone'));
    }
}
