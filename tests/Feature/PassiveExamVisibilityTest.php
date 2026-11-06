<?php

namespace Tests\Feature;

use App\Actions\Tests\StartTestResult;
use App\Enums\StatusEnum;
use App\Livewire\Frontend\Exams\ListExams;
use App\Models\Language;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class PassiveExamVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    public static function detailCases(): array
    {
        $cases = [];
        foreach (['nl', 'tr', 'en', 'fr'] as $locale) {
            foreach (['active', 'passive'] as $status) {
                foreach (['guest', 'allowed', 'denied'] as $actor) {
                    $cases[$locale.'-'.$status.'-'.$actor] = [$locale, $status, $actor];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('detailCases')]
    public function test_exam_detail_is_readable_but_only_active_permitted_members_can_start(string $locale, string $status, string $actor): void
    {
        Queue::fake();
        Language::firstOrCreate(['code' => $locale], ['name' => $locale, 'status' => StatusEnum::ACTIVE]);
        $this->withSession(['locale' => $locale]);
        $user = User::factory()->create();
        Permission::findOrCreate('tests:solve', 'web');
        if ($actor === 'allowed') {
            $user->givePermissionTo('tests:solve');
        }
        if ($actor !== 'guest') {
            $this->actingAs($user);
        }
        $test = Test::create(['code' => 'visibility-'.$status, 'name' => 'Visible exam title', 'content' => '',
            'locale' => $locale, 'duration' => 120, 'passing_score' => 50, 'status' => $status, 'created_by' => $user->id]);
        $response = $this->get(route('frontend.exam.detail', $test->code))->assertOk()->assertSee('Visible exam title');
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $startUrl = route('frontend.exam.start', $test->code);
        if ($status === 'passive') {
            $this->assertSame(1, $xpath->query('//button[@disabled and contains(normalize-space(.), "'.__('learning.coming_soon').'")]')->length);
            $response->assertDontSee('href="'.$startUrl.'"', false);
            $this->get($startUrl)->assertRedirect($actor === 'guest' ? route('login') : route('frontend.exam.detail', $test->code));
            $this->assertSame(0, $test->results()->count());
            if ($actor === 'allowed') {
                try {
                    StartTestResult::run($test);
                    $this->fail('Passive exam must also be blocked by the action.');
                } catch (HttpExceptionInterface $exception) {
                    $this->assertSame(403, $exception->getStatusCode());
                }
            }
        } elseif ($actor === 'allowed') {
            $response->assertSee('href="'.$startUrl.'"', false)->assertDontSee(__('learning.coming_soon'));
            $this->assertSame($user->id, StartTestResult::run($test)->user_id);
        } else {
            $response->assertDontSee('href="'.$startUrl.'"', false)->assertDontSee(__('learning.coming_soon'));
            $this->get($startUrl)->assertRedirect($actor === 'guest' ? route('login') : route('frontend.exam.detail', $test->code));
        }
    }

    public function test_both_statuses_still_require_a_parent_section_and_question_to_appear_in_the_list(): void
    {
        $ids = [];
        foreach (['active', 'passive'] as $status) {
            foreach (['empty', 'section-only', 'question-only', 'complete'] as $kind) {
                $test = Test::create(['code' => 'visibility-'.$status.'-'.$kind, 'name' => 'Exam '.$kind,
                    'locale' => 'nl', 'status' => $status, 'created_by' => 1]);
                $ids[$status][$kind] = $test->id;
                if (in_array($kind, ['section-only', 'complete'], true)) {
                    $test->sections()->create(['parent_id' => 0, 'name' => 'Section', 'type' => 'topic']);
                }
                if (in_array($kind, ['question-only', 'complete'], true)) {
                    $section = $test->sections()->create(['parent_id' => 99, 'name' => 'Question', 'type' => 'question']);
                    $test->questions()->create(['section_id' => $section->id, 'question_id' => 1, 'lesson_id' => 1, 'topic_id' => 1]);
                }
            }
        }
        $list = new ListExams;
        $list->search = true;
        $list->word = 'visibility-';
        $this->assertEqualsCanonicalizing([$ids['active']['complete'], $ids['passive']['complete']], collect($list->exams()->items())->pluck('id')->all());
        $list->word = 'visibility-passive-complete';
        $this->assertSame([$ids['passive']['complete']], collect($list->exams()->items())->pluck('id')->all());
    }
}
