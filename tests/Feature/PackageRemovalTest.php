<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;

class PackageRemovalTest extends TestCase
{
    public function test_schedule_keeps_application_tasks_without_removed_package_jobs(): void
    {
        // TestCase replaces the console kernel to prevent optimize:clear.
        $this->app->forgetInstance(Kernel::class);
        $this->app->make(Kernel::class)->all();
        $names = array_map(fn ($event) => $event->description, app(Schedule::class)->events());

        $this->assertSame([
            'communications_dispatch',
            'update_gpt_limit_for_users',
            'update_season_id_for_results',
            'cache_clear',
            'run_league_result',
            'refresh_live_leagues',
        ], $names);
        $communication = app(Schedule::class)->events()[0];
        $this->assertSame('* * * * *', $communication->expression);
        $this->assertTrue($communication->withoutOverlapping);
    }
}
