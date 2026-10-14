<?php

namespace App\Actions\Study;

use App\Helpers\StudyCalendar;
use App\Models\StudyWeek;
use Lorisleiva\Actions\Concerns\AsAction;

class EnsureStudyWeek
{
    use AsAction;

    public function handle(string $date): StudyWeek
    {
        $bounds = StudyCalendar::bounds($date);

        return StudyWeek::firstOrCreate(['starts_on' => $bounds['start']->toDateString()], [
            'ends_on' => $bounds['end']->subDay()->toDateString(),
        ]);
    }
}
