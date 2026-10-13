<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DefaultLocaleTest extends TestCase
{
    protected function setUp(): void
    {
        if (($_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE')) !== 'testing') {
            throw new \RuntimeException('DefaultLocaleTest yalnız testing veritabanında çalışır.');
        }

        parent::setUp();
    }

    public function test_new_session_uses_nl_without_a_saved_default(): void
    {
        $this->assertSame('testing', DB::connection()->getDatabaseName());

        $request = Request::create('/');
        $request->setLaravelSession(app('session')->driver());

        app(SetLocale::class)->handle($request, fn () => response('OK'));

        $this->assertSame('nl', app()->getLocale());
        $this->assertSame('nl', $request->session()->get('locale'));
    }
}
