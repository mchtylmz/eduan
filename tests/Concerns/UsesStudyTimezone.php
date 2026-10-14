<?php

namespace Tests\Concerns;

trait UsesStudyTimezone
{
    private string $originalStudyTimezone;

    private function useStudyTimezone(): void
    {
        $this->originalStudyTimezone = date_default_timezone_get();
        config(['app.timezone' => 'Europe/Brussels']);
        date_default_timezone_set('Europe/Brussels');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalStudyTimezone);
        parent::tearDown();
    }
}
