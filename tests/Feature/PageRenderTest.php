<?php

namespace Tests\Feature;

use App\Enums\PageMenuEnum;
use App\Enums\StatusEnum;
use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageRenderTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Queue::fake();
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        settings()->set(['defaultLocale' => 'nl', 'examlanguageCode' => 'nl', 'testlanguageCode' => 'nl', 'leagueStatus' => 'active', 'registerStatus' => 'active']);
        settings()->save();
        Page::firstOrCreate(['slug' => 'home'], ['menu' => PageMenuEnum::HOME, 'title' => ['nl' => 'Home'], 'content' => ['nl' => []]]);
    }

    public static function pages(): array
    {
        $pages = [];
        foreach (['home', 'contacts', 'newsletter', 'blogs', 'pages.faqs', 'lessons', 'topics', 'questions', 'exams',
            'tests', 'users', 'roles', 'stats', 'settings', 'languages', 'ai', 'leagues', 'seasons'] as $resource) {
            $pages["panel-$resource"] = ["admin.$resource.index", true, []];
        }
        foreach (['blogs', 'pages', 'pages.faqs', 'lessons', 'topics', 'questions', 'exams', 'tests', 'users', 'roles', 'languages'] as $resource) {
            $pages["create-$resource"] = ["admin.$resource.create", true, []];
        }
        $pages['panel-pages-home'] = ['admin.pages.home', true, []];
        $pages['panel-pages-all'] = ['admin.pages.all', true, []];
        foreach (['general', 'ai', 'popup', 'logo', 'style', 'cover', 'exam', 'league', 'test', 'email', 'emailTemplate', 'user', 'contact'] as $tab) {
            $pages["settings-$tab"] = ['admin.settings.index', true, ['activeTab' => $tab]];
        }
        foreach (['home', 'search', 'tests', 'exams', 'lessons', 'blog', 'faqs', 'leagues', 'contact', 'register', 'recover.password'] as $page) {
            $pages["site-$page"] = ["frontend.$page", false, []];
        }
        foreach (['profile', 'favorite', 'stats', 'my.leagues', 'solved', 'solved.notTests', 'solved.exams'] as $page) {
            $pages["account-$page"] = ["frontend.$page", true, []];
        }
        $pages['login'] = ['login', false, []];
        $pages['pwa'] = ['pwa', false, []];
        foreach (['admin.lessons.index', 'admin.topics.index', 'admin.questions.index', 'admin.exams.index', 'admin.tests.index',
            'frontend.lessons', 'frontend.tests', 'frontend.exams', 'frontend.faqs', 'frontend.profile'] as $route) {
            $pages["hydrated-$route"] = [$route, str_starts_with($route, 'admin.') || $route === 'frontend.profile', [], true];
        }

        return $pages;
    }

    public function test_legacy_offline_route_returns_its_existing_fragment(): void
    {
        $this->get(route('offline'))->assertOk()->assertSee('<h3>offline</h3>', false);
    }

    public function test_disabled_registration_redirects_to_home(): void
    {
        settings()->set('registerStatus', 'passive');
        settings()->save();
        $this->get(route('frontend.register'))->assertRedirect(route('frontend.home'));
    }

    #[DataProvider('pages')]
    public function test_page_renders_with_its_required_access(string $route, bool $signedIn, array $parameters, bool $hydrate = false): void
    {
        if ($signedIn) {
            $user = User::factory()->create();
            $permissions = ['dashboard:access'];
            foreach (['contacts', 'newsletter', 'blogs', 'pages', 'lessons', 'topics', 'questions', 'exams', 'tests',
                'users', 'roles', 'stats', 'settings', 'languages', 'ai', 'leagues', 'seasons'] as $resource) {
                foreach (['view', 'add', 'update', 'delete'] as $operation) {
                    $permissions[] = "$resource:$operation";
                }
            }
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
            $user->givePermissionTo($permissions);
            $this->actingAs($user);
        }
        if ($hydrate) {
            Livewire::withoutLazyLoading();
        }
        $this->get(route($route, $parameters))->assertOk()->assertSee('<html', false);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        Queue::assertNothingPushed();
    }
}
