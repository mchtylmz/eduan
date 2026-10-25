<?php

namespace App\Helpers;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class LeaguePeriod
{
    public function __construct(public readonly CarbonImmutable $start, public readonly CarbonImmutable $end)
    {
        if ($end->lessThan($start)) {
            throw new InvalidArgumentException('League end precedes start.');
        }
    }

    public static function fromStrings(string $start, string $end): self
    {
        return new self(CarbonImmutable::parse($start)->startOfMinute(), CarbonImmutable::parse($end)->endOfMinute()->setMicrosecond(0));
    }

    /** Defaults only: custom periods and stored historical dates are not restricted. */
    public static function currentWeek(CarbonImmutable $now): self
    {
        $start = $now->startOfWeek(CarbonInterface::SATURDAY)->startOfDay();

        return new self($start, $start->addDays(6)->endOfDay()->setMicrosecond(0));
    }

    public function cutoff(CarbonImmutable $now): ?CarbonImmutable
    {
        return $now->lessThan($this->start) ? null : $now->min($this->end)->setMicrosecond(0);
    }

    public function lockKey(string $source): string
    {
        return 'league:'.sha1($source.'|'.$this->start->format('Y-m-d H:i:s').'|'.$this->end->format('Y-m-d H:i:s'));
    }
}
