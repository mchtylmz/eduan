<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Livewire\Mechanisms\HandleRequests\RequireLivewireHeaders;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class LivewireUpdateRouteCacheTest extends TestCase
{
    public function test_client_uses_the_custom_update_endpoint_with_and_without_route_cache(): void
    {
        $root = sys_get_temp_dir().'/eduan-route-cache-'.bin2hex(random_bytes(8));
        $files = new Filesystem;

        try {
            foreach (['bootstrap/cache', 'config', 'routes', 'storage/framework/views', 'resources/views'] as $directory) {
                $files->makeDirectory($root.'/'.$directory, 0700, true);
            }

            // Isolate package discovery, configuration and cache from the real application.
            $files->put($root.'/bootstrap/providers.php', '<?php return [];');
            $files->put($root.'/bootstrap/cache/packages.php', '<?php return [];');
            $files->put($root.'/routes/web.php', '<?php');
            $files->put($root.'/config/app.php', <<<'PHP'
<?php
return ['name' => 'Route cache probe', 'env' => 'testing', 'debug' => true,
    'key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
    'cipher' => 'AES-256-CBC', 'url' => 'http://localhost'];
PHP);
            $files->put($root.'/config/view.php', <<<'PHP'
<?php
return ['paths' => [resource_path('views')], 'compiled' => storage_path('framework/views')];
PHP);
            $files->put($root.'/config/cache.php', <<<'PHP'
<?php
return ['default' => 'array', 'stores' => ['array' => ['driver' => 'array']]];
PHP);
            $files->put($root.'/bootstrap/app.php', <<<'PHP'
<?php
return Illuminate\Foundation\Application::configure(basePath: dirname(__DIR__))
    ->withProviders([Livewire\LivewireServiceProvider::class, App\Providers\AppServiceProvider::class])
    ->withRouting(web: dirname(__DIR__).'/routes/web.php')
    ->create();
PHP);

            $script = <<<'PHP'
require $argv[1].'/vendor/autoload.php';

function bootProbe($root) {
    Illuminate\Support\Facades\Facade::clearResolvedInstances();
    $app = require $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    return $app;
}

function inspectProbe($app) {
    $route = $app['router']->getRoutes()->getByName('livewire.update');
    return [
        'cached' => $app->routesAreCached(),
        'client_uri' => $app['livewire']->getUpdateUri(),
        'route_uri' => $route->uri(),
        'methods' => $route->methods(),
        'middleware' => $route->middleware(),
    ];
}

$app = bootProbe($argv[2]);
$before = inspectProbe($app);
if ($app->make(Illuminate\Contracts\Console\Kernel::class)->call('route:cache') !== 0) {
    throw new RuntimeException('Route cache generation failed.');
}
$app = bootProbe($argv[2]);
echo json_encode(['uncached' => $before, 'cached' => inspectProbe($app)], JSON_THROW_ON_ERROR);
PHP;

            $process = new Process([PHP_BINARY, '-r', $script, dirname(__DIR__, 2), $root]);
            $process->setTimeout(30);
            $process->mustRun();
            $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

            foreach (['uncached' => false, 'cached' => true] as $mode => $cached) {
                $this->assertSame($cached, $result[$mode]['cached']);
                $this->assertSame('/livewire/update', $result[$mode]['client_uri']);
                $this->assertSame('livewire/update', $result[$mode]['route_uri']);
                $this->assertSame(['POST'], $result[$mode]['methods']);
                $this->assertContains('web', $result[$mode]['middleware']);
                $this->assertContains(RequireLivewireHeaders::class, $result[$mode]['middleware']);
            }
        } finally {
            $files->deleteDirectory($root);
        }
    }
}
