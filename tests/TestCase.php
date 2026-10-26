<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        parent::actingAs($user, $guard);
        $guard ??= $this->app['auth']->getDefaultDriver();
        // Match the session proof established by the application's real LoginAction.
        if ($user->getAuthPassword()) {
            $this->withSession(['password_hash_'.$guard => $this->app['auth']->guard($guard)->hashPasswordForCookie($user->getAuthPassword())]);
        }

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Model events must not clear the running application's compiled files.
        Artisan::partialMock()
            ->shouldReceive('call')->with('optimize:clear')->andReturn(0);
        Http::preventStrayRequests();
    }

    public function createApplication()
    {
        if (($_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE')) !== 'testing') {
            throw new \RuntimeException('Testler yalnız testing veritabanında çalışır.');
        }

        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        if ($app['config']->get("database.connections.$connection.database") !== 'testing') {
            throw new \RuntimeException('Test bağlantısı testing veritabanını kullanmalıdır.');
        }

        return $app;
    }
}
