<?php

namespace Tests\Feature;

use App\Livewire\Frontend\Lessons\ListTopics;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TopicSearchContextTest extends TestCase
{
    use DatabaseTransactions;

    public static function searchInputs(): array
    {
        return [['missing'], [''], ['   '], ['needle']];
    }

    private function fixtures(): array
    {
        $topics = [];
        foreach (['alpha', 'beta'] as $index => $name) {
            $lesson = Lesson::create(['code' => 'qa-context-'.$name, 'name' => ['nl' => 'Lesson '.$name], 'status' => 'active']);
            $topics[] = Topic::create(['lesson_id' => $lesson->id, 'code' => 'qa-context-needle-'.$name,
                'title' => ['nl' => 'Needle '.$name], 'sort' => $index, 'status' => 'active']);
        }

        return $topics;
    }

    #[DataProvider('searchInputs')]
    public function test_general_topic_search_without_a_lesson_renders_over_http(string $word): void
    {
        $this->fixtures();
        $query = ['tab' => 'topics'];
        if ($word !== 'missing') {
            $query['search'] = $word;
        }
        $response = $this->withSession(['locale' => 'nl'])->get(route('frontend.search', $query))->assertOk();
        if ($word !== '   ') {
            $response->assertSee('Needle alpha')->assertSee('Needle beta');
        }
    }

    public function test_general_and_lesson_scoped_lists_keep_distinct_results_during_livewire_search(): void
    {
        [$alpha, $beta] = $this->fixtures();
        app()->setLocale('nl');
        Livewire::withoutLazyLoading()->test(ListTopics::class, ['visibilityLite' => true])
            ->set('word', 'needle')->call('searchTopics')
            ->assertSee('Needle alpha')->assertSee('Needle beta');
        Livewire::withoutLazyLoading()->test(ListTopics::class, ['lesson' => $alpha->lesson, 'visibilityLite' => true])
            ->assertSee('Needle alpha')->assertDontSee('Needle beta')
            ->set('word', 'needle')->call('searchTopics')
            ->assertSee('Needle alpha')->assertDontSee('Needle beta')
            ->call('clearSearch')->assertSee('Needle alpha')->assertDontSee('Needle beta');
        $list = new ListTopics;
        $list->search = true;
        $list->word = 'qa-context-needle-';
        $this->assertEqualsCanonicalizing([$alpha->id, $beta->id], collect($list->topics()->items())->pluck('id')->all());
    }
}
