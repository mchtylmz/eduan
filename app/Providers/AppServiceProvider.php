<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserImpersonation;
use App\Models\Answer;
use App\Models\AnswerAI;
use App\Models\AnswerAIVote;
use App\Models\Blog;
use App\Models\Contact;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamResultDetail;
use App\Models\ExamReview;
use App\Models\Faq;
use App\Models\Language;
use App\Models\LatexImage;
use App\Models\League;
use App\Models\LeagueResult;
use App\Models\Lesson;
use App\Models\Log;
use App\Models\Newsletter;
use App\Models\Page;
use App\Models\Question;
use App\Models\Role;
use App\Models\Season;
use App\Models\Test;
use App\Models\TestsQuestion;
use App\Models\TestsResult;
use App\Models\TestsResultDetail;
use App\Models\TestsSection;
use App\Models\Topic;
use App\Models\Translation;
use App\Models\User;
use App\Models\UserAiUsage;
use App\Observers\TableCacheObserver;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

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
        $this->bindings();
        Livewire::addPersistentMiddleware([EnsureUserImpersonation::class]);

        RedirectIfAuthenticated::redirectUsing(function () {
            if (request()->user()->can('dashboard:access')) {
                return route('admin.home.index');
            }

            return route('frontend.profile');
        });

        if (app()->environment('production')) {
            Facades\URL::forceScheme('https');
            request()->server->set('HTTPS', 'on');
        }

        Paginator::useBootstrapFive();

        if (app()->environment('production')) {
            $this->overrideTimezone();
            $this->overrideMailConfig();
        }

        $this->viewComposer();
        $this->registerMacros();

        foreach ([Lesson::class, Topic::class, Question::class,
            User::class, Role::class, Language::class,
            Translation::class, Permission::class, Log::class,
            Blog::class, Page::class, Faq::class, Contact::class, Newsletter::class, ExamReview::class, Exam::class, AnswerAI::class, AnswerAIVote::class, UserAiUsage::class, LatexImage::class, ExamResult::class, ExamResultDetail::class, Answer::class, Season::class, Test::class, TestsResult::class, TestsResultDetail::class, TestsSection::class, TestsQuestion::class, League::class, LeagueResult::class] as $model) {
            $model::observe(TableCacheObserver::class);
        }
    }

    public function viewComposer(): void
    {
        Facades\View::composer('*', function (View $view) {});
    }

    public function registerMacros(): void {}

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

    protected function bindings(): void
    {
        if ($public_path = config('app.public_path')) {
            // $this->app->instance('path.public', base_path($public_path));
            $this->app->usePublicPath(base_path($public_path));
        }
    }
}
