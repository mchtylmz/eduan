<?php

namespace Tests\Feature;

use App\Livewire\Events;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FrameworkUpgradeTest extends TestCase
{
    public static function alerts(): array
    {
        return [
            'success toast' => ['success', true, 'top-end'],
            'error dialog' => ['error', false, 'center'],
        ];
    }

    #[DataProvider('alerts')]
    public function test_alert_keeps_message_and_display_options(string $type, bool $toast, string $position): void
    {
        $message = 'Saved "safely" <script>example</script>';
        $component = Livewire::test(Events::class)
            ->call('message', $message)
            ->call('toast', $toast, $position)
            ->call($type);

        $script = $component->effects['xjs'][0]['expression'];
        $this->assertStringContainsString('Swal.fire(options)', $script);
        $this->assertSame(1, preg_match('/const options = ([^\n]+)/', $script, $matches));
        $options = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($message, $options['text']);
        $this->assertSame($type, $options['icon']);
        $this->assertSame($toast, $options['toast']);
        $this->assertSame($position, $options['position']);
        $this->assertSame(3000, $options['timer']);
        $this->assertTrue($options['timerProgressBar']);
        $this->assertTrue($options['showCloseButton']);
    }

    public function test_served_disks_have_distinct_routes_and_require_a_signature(): void
    {
        $this->assertSame('/storage/missing.txt', route('storage.local', ['path' => 'missing.txt'], false));
        $this->assertSame('/private-storage/missing.txt', route('storage.private', ['path' => 'missing.txt'], false));
        $this->get('/storage/missing.txt')->assertForbidden();
        $this->get('/private-storage/missing.txt')->assertForbidden();
    }
}
