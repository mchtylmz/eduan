<?php

namespace Tests\Unit;

use App\Livewire\Frontend\Blogs\ListBlogs;
use App\Livewire\Frontend\Lessons\ListLessons;
use App\Livewire\Frontend\Lessons\ListTopics;
use App\Livewire\Frontend\Tests\ListTests;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ProductionLogRegressionTest extends TestCase
{
    public static function phpModes(): array
    {
        return [[0], [1]];
    }

    #[DataProvider('phpModes')]
    public function test_sitemap_compiles_and_renders_valid_xml_with_short_tags_on_or_off(int $shortTags): void
    {
        $script = <<<'PHP'
require $argv[1].'/vendor/autoload.php';
$app = new Illuminate\Foundation\Application($argv[1]);
$app->instance('config', new Illuminate\Config\Repository([
    'view' => ['paths' => [$argv[1].'/resources/views'], 'compiled' => sys_get_temp_dir()],
]));
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
$app->register(Illuminate\Filesystem\FilesystemServiceProvider::class);
$app->register(Illuminate\View\ViewServiceProvider::class);
$source = file_get_contents($argv[1].'/resources/views/frontend/home/sitemap.blade.php');
$compiled = $app['blade.compiler']->compileString($source);
token_get_all($compiled, TOKEN_PARSE);
$entries = [['loc' => 'https://hypotenuse.test/lesson/example?a=1&b=2',
    'lastmod' => '2026-11-06T00:00:00+00:00', 'alternates' => ['nl' => 'https://hypotenuse.test/?lang=nl']]];
$__env = $app['view'];
ob_start();
eval('?>'.$compiled);
echo ob_get_clean();
PHP;
        $process = new Process([PHP_BINARY, '-d', 'short_open_tag='.$shortTags, '-r', $script, dirname(__DIR__, 2)]);
        $process->setTimeout(10)->mustRun();
        $xml = $process->getOutput();
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $document = simplexml_load_string($xml);
        $this->assertNotFalse($document);
        $this->assertSame('https://hypotenuse.test/lesson/example?a=1&b=2', (string) $document->url->loc);
        $this->assertSame('2026-11-06T00:00:00+00:00', (string) $document->url->lastmod);
        $this->assertSame('nl', (string) $document->url->children('http://www.w3.org/1999/xhtml')->link->attributes()['hreflang']);
    }

    public static function searchInputs(): array
    {
        $cases = [];
        foreach (['lessons', 'topics', 'exams', 'newspaper'] as $tab) {
            foreach (['missing' => null, 'empty' => '', 'spaces' => '   ', 'normal' => 'breuken & procenten'] as $name => $value) {
                $cases[$tab.'-'.$name] = [$tab, $value];
            }
        }

        return $cases;
    }

    #[DataProvider('searchInputs')]
    public function test_search_view_never_passes_null_to_string_component_inputs(string $tab, ?string $word): void
    {
        $previousContainer = Container::getInstance();
        $previousFacades = Facade::getFacadeApplication();
        $temporary = sys_get_temp_dir().'/eduan-log-search-'.bin2hex(random_bytes(6));
        $files = new Filesystem;
        $files->makeDirectory($temporary.'/frontend/layouts', 0700, true);
        $files->makeDirectory($temporary.'/compiled', 0700, true);
        $files->makeDirectory($temporary.'/components/seo', 0700, true);
        $files->put($temporary.'/frontend/layouts/app.blade.php', '@yield("content")');
        $files->put($temporary.'/components/seo/tags.blade.php', '');

        try {
            $app = new Application(dirname(__DIR__, 2));
            $app->instance('config', new Repository([
                'app' => ['locale' => 'nl', 'fallback_locale' => 'en'],
                'view' => ['paths' => [$temporary, dirname(__DIR__, 2).'/resources/views'], 'compiled' => $temporary.'/compiled'],
            ]));
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($app);
            $query = ['tab' => $tab];
            if ($word !== null) {
                $query['search'] = trim($word) === '' ? null : $word;
            }
            $request = Request::create('http://localhost/search', 'GET', $query);
            $app->instance('request', $request);
            $app->instance('Settings', new class
            {
                public ?string $siteLogo = null;
            });
            foreach ([FilesystemServiceProvider::class, TranslationServiceProvider::class, ViewServiceProvider::class, RoutingServiceProvider::class] as $provider) {
                $app->register($provider);
            }
            $session = new Store('log-search', new ArraySessionHandler(10));
            $session->start();
            $request->setLaravelSession($session);
            $app->instance('session', $session);
            $app['router']->get('/search', fn () => '')->name('frontend.search');
            $app['router']->getRoutes()->refreshNameLookups();
            $app['url']->setRoutes($app['router']->getRoutes());
            // Keep the real Blade template and component types; isolate DB queries and the outer layout.
            $app['blade.compiler']->directive('livewire', fn ($expression) => '<?php $mount('.$expression.'); ?>');
            $mounted = [];
            $mount = function (string $name, array $parameters) use (&$mounted): void {
                $class = match ($name) {
                    'frontend.lessons.list-lessons' => ListLessons::class,
                    'frontend.lessons.list-topics' => ListTopics::class,
                    'frontend.tests.list-tests' => ListTests::class,
                    'frontend.blogs.list-blogs' => ListBlogs::class,
                };
                $component = new $class;
                foreach ($parameters as $property => $value) {
                    $component->$property = $value;
                }
                $mounted[] = $component;
            };
            $html = $app['view']->make('frontend.home.search', ['title' => 'Search', 'mount' => $mount])->render();
            $this->assertStringContainsString('name="search"', $html);
            $this->assertCount(1, $mounted);
            $expected = $word === null || trim($word) === '' ? '' : $word;
            $this->assertSame($expected, $tab === 'newspaper' ? $mounted[0]->search : $mounted[0]->word);
        } finally {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($previousFacades);
            Container::setInstance($previousContainer);
            $files->deleteDirectory($temporary);
        }
    }
}
