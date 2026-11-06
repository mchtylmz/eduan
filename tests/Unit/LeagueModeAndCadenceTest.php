<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class LeagueModeAndCadenceTest extends TestCase
{
    public function test_real_form_requires_explicit_mode_and_schedule_obeys_amsterdam_day_and_night(): void
    {
        $root = sys_get_temp_dir().'/eduan-league-mode-'.bin2hex(random_bytes(8));
        $source = dirname(__DIR__, 2);
        $files = new Filesystem;

        try {
            foreach (['bootstrap/cache', 'config', 'routes', 'storage/framework/views'] as $directory) {
                $files->makeDirectory($root.'/'.$directory, 0700, true);
            }
            $files->put($root.'/composer.json', '{"autoload":{"psr-4":{"App\\\\":"app/"}}}');
            $files->put($root.'/bootstrap/providers.php', '<?php return [Livewire\\LivewireServiceProvider::class];');
            $files->put($root.'/bootstrap/cache/packages.php', '<?php return [];');
            $files->put($root.'/routes/web.php', '<?php Illuminate\\Support\\Facades\\Route::get("/leagues", fn () => "")->name("admin.leagues.index");');
            // Run the actual schedule declarations with isolated config, routes and caches.
            $files->put($root.'/bootstrap/app.php', $files->get($source.'/bootstrap/app.php'));
            $config = [
                'app' => ['env' => 'testing', 'debug' => true, 'locale' => 'tr', 'fallback_locale' => 'en',
                    'timezone' => 'Europe/Brussels', 'key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
                    'cipher' => 'AES-256-CBC', 'url' => 'http://localhost'],
                'view' => ['paths' => [$source.'/resources/views'], 'compiled' => $root.'/storage/framework/views'],
                'cache' => ['default' => 'array', 'stores' => ['array' => ['driver' => 'array']]],
                'session' => ['driver' => 'array', 'lifetime' => 120, 'cookie' => 'league-mode-test', 'lottery' => [0, 100]],
            ];
            foreach ($config as $name => $values) {
                $files->put($root.'/config/'.$name.'.php', '<?php return '.var_export($values, true).';');
            }

            $script = <<<'SCRIPT'
require $argv[1].'/vendor/autoload.php';
$app = require $argv[2].'/bootstrap/app.php';
$app->useLangPath($argv[1].'/lang');
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->instance('Settings', new class {
    public string $leagueStatus = 'active';
});
$actor = new class extends App\Models\User {
    public function can($abilities, $arguments = []): bool { return true; }
};
$app->instance('auth', new class($actor) implements Illuminate\Contracts\Auth\Factory {
    public function __construct(private $actor) {}
    public function guard($name = null): self { return $this; }
    public function shouldUse($name): void {}
    public function user() { return $this->actor; }
    public function check(): bool { return true; }
});
Carbon\CarbonImmutable::setTestNow(Carbon\CarbonImmutable::parse('2026-10-28 12:00', 'Europe/Brussels'));
$results = [];
foreach (['tr', 'nl', 'en', 'fr'] as $locale) {
    $app->setLocale($locale);
    $component = Livewire\Livewire::test(App\Livewire\Leagues\LeagueRunForm::class)
        ->assertSet('is_live', '')
        ->assertSet('start_at', '2026-10-24 00:00')
        ->assertSet('end_at', '2026-10-30 23:59');
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$component->html());
    $xpath = new DOMXPath($dom);
    $placeholder = $xpath->query('//select[@id="league-mode"]/option[@value=""]')->item(0);
    $results['locales'][$locale] = [
        'placeholder' => trim($placeholder->textContent),
        'hidden' => $placeholder->hasAttribute('hidden'),
        'disabled' => $placeholder->hasAttribute('disabled'),
        'selected' => $placeholder->hasAttribute('selected'),
        'options' => $xpath->query('//select[@id="league-mode"]/option')->length,
    ];
}
$mock = App\Actions\Leagues\CalculateLeague::mock();
$app->instance(App\Actions\Leagues\CalculateLeague::class, $mock);
$mock->shouldReceive('handle')->with('2026-10-24 00:00', '2026-10-30 23:59', false)->once()->andReturn(new App\Models\League);
$mock->shouldReceive('handle')->with('2026-10-24 00:00', '2026-10-30 23:59', true)->once()->andReturn(new App\Models\League);
foreach (['', 'invalid', '2'] as $value) {
    Livewire\Livewire::test(App\Livewire\Leagues\LeagueRunForm::class)
        ->set('is_live', $value)->call('run')->assertHasErrors('is_live');
}
foreach (['0', '1'] as $value) {
    Livewire\Livewire::test(App\Livewire\Leagues\LeagueRunForm::class)
        ->set('is_live', $value)->call('run')->assertHasNoErrors()->assertRedirect('http://localhost/leagues');
}
Mockery::close();
$results['form_cases'] = 5;
$app->make(Illuminate\Contracts\Console\Kernel::class)->all();
$events = collect($app->make(Illuminate\Console\Scheduling\Schedule::class)->events());
$event = $events->firstWhere('description', 'refresh_live_leagues');
$results['schedule'] = [$event->expression, $event->timezone, $event->withoutOverlapping, $event->expiresAt];
foreach (['2026-01-15', '2026-07-15', '2026-03-29', '2026-10-25'] as $day) {
    $start = Carbon\CarbonImmutable::parse($day, 'Europe/Amsterdam')->startOfDay();
    $end = $start->addDay()->utc();
    for ($time = $start->utc(); $time->lessThan($end); $time = $time->addMinute()) {
        Carbon\Carbon::setTestNow($time);
        $local = $time->setTimezone('Europe/Amsterdam');
        $expected = $local->hour >= 9 ? $local->minute % 15 === 0 : $local->minute === 0;
        $results['slots'][] = [$local->format('c'), $expected, $event->isDue($app) && $event->filtersPass($app)];
    }
}
Carbon\Carbon::setTestNow();
$results['legacy_schedule'] = $events->firstWhere('description', 'run_league_result')->expression;
echo json_encode($results, JSON_THROW_ON_ERROR);
SCRIPT;

            $process = new Process([PHP_BINARY, '-r', $script, $source, $root]);
            $process->setTimeout(30);
            $this->assertSame(0, $process->run(), substr($process->getOutput().$process->getErrorOutput(), 0, 2500));
            $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            foreach (['tr', 'nl', 'en', 'fr'] as $locale) {
                $messages = require $source.'/lang/'.$locale.'/leagues.php';
                $this->assertSame([
                    'placeholder' => $messages['mode_placeholder'], 'hidden' => true, 'disabled' => true, 'selected' => true, 'options' => 3,
                ], $result['locales'][$locale]);
            }
            $this->assertSame(5, $result['form_cases']);
            $this->assertSame(['*/15 * * * *', 'Europe/Amsterdam', true, 240], $result['schedule']);
            $this->assertCount(5760, $result['slots']);
            $this->assertSame(276, count(array_filter($result['slots'], fn ($slot) => $slot[2])));
            foreach ($result['slots'] as [$time, $expected, $due]) {
                $this->assertSame($expected, $due, $time);
            }
            $this->assertSame('0 * * * *', $result['legacy_schedule']);
        } finally {
            $files->deleteDirectory($root);
        }
    }
}
