<?php

namespace Tests\Feature;

use App\Actions\Study\AuthorizeStudyChange;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesStudyTimezone;
use Tests\TestCase;

class StudyReleaseCombinationsTest extends TestCase
{
    use DatabaseTransactions, UsesStudyTimezone;

    public static function changeCombinations(): iterable
    {
        foreach (['student', 'teacher', 'admin', 'hybrid', 'unlinked_teacher', 'readonly_teacher'] as $role) {
            foreach ([false, true] as $enabled) {
                foreach ([false, true] as $pastAllowed) {
                    foreach (['before_start' => '2026-09-19', 'closed' => '2026-10-03', 'current' => '2026-10-14', 'future' => '2026-10-17'] as $period => $date) {
                        foreach (['target', 'activity'] as $kind) {
                            yield implode('/', [$role, (int) $enabled, (int) $pastAllowed, $period, $kind]) => [$role, $enabled, $pastAllowed, $period, $date, $kind];
                        }
                    }
                }
            }
        }
    }

    #[DataProvider('changeCombinations')]
    public function test_every_role_module_past_setting_date_and_change_kind_combination(string $role, bool $enabled, bool $pastAllowed, string $period, string $date, string $kind): void
    {
        $this->useStudyTimezone();
        $this->travelTo(now()->setDateTime(2026, 10, 14, 12, 0, 0));
        foreach (['study:access', 'study:view', 'study:update', 'dashboard:access', 'users:view', 'user-type:user', 'user-type:teacher', 'user-type:admin'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $owner = User::factory()->create();
        $owner->givePermissionTo(['study:access', 'user-type:user']);
        $actor = $role === 'student' ? $owner : User::factory()->create();
        if ($role !== 'student') {
            $actor->givePermissionTo(['study:access', 'study:view', 'study:update', 'dashboard:access', 'users:view']);
            $actor->givePermissionTo(in_array($role, ['admin', 'hybrid'], true) ? 'user-type:admin' : 'user-type:teacher');
            if ($role === 'hybrid') {
                $actor->givePermissionTo('user-type:teacher');
            }
            if ($role !== 'unlinked_teacher') {
                $actor->students()->attach($owner);
            }
            if ($role === 'readonly_teacher') {
                $actor->revokePermissionTo('study:update');
            }
        }
        $this->actingAs($actor);
        settings()->set(['studyTrackingEnabled' => (string) (int) $enabled, 'studyTrackingStartedOn' => '2026-10-01', 'studyAllowPastActivities' => (string) (int) $pastAllowed]);
        settings()->save();

        $admin = in_array($role, ['admin', 'hybrid'], true);
        $expected = match (true) {
            in_array($role, ['unlinked_teacher', 'readonly_teacher'], true), ! $enabled => 403,
            in_array($period, ['before_start', 'future'], true) => 422,
            $period === 'closed' && ! $admin && ($kind === 'target' || ! $pastAllowed) => 403,
            default => 200,
        };
        $actual = 200;
        try {
            AuthorizeStudyChange::run($owner, $kind, $date);
        } catch (HttpException $exception) {
            $actual = $exception->getStatusCode();
        }
        $this->assertSame($expected, $actual);
    }
}
