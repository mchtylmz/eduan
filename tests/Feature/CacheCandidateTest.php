<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Livewire\Frontend\Blogs\ListBlogs;
use App\Livewire\Frontend\Exams\ListExams;
use App\Livewire\Frontend\Leagues\LeagueResultTable;
use App\Livewire\Frontend\Lessons\ListLessons;
use App\Livewire\Frontend\Lessons\ListTopics;
use App\Livewire\Frontend\Tests\ListTests;
use App\Models\Blog;
use App\Models\Exam;
use App\Models\League;
use App\Models\Lesson;
use App\Models\Test;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CacheCandidateTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::swap(\Mockery::spy(Kernel::class));
    }

    public static function searches(): array
    {
        return [
            'lessons' => [ListLessons::class, 'lessons', 'searchLesson'],
            'topics' => [ListTopics::class, 'topics', 'searchTopics'],
            'practice' => [ListTests::class, 'tests', 'searchLesson'],
            'timed' => [ListExams::class, 'exams', 'searchExams'],
        ];
    }

    #[DataProvider('searches')]
    public function test_search_activation_does_not_reuse_the_unfiltered_cache(string $class, string $query, string $search): void
    {
        $lesson = Lesson::create(['code' => 'cache-lesson', 'name' => ['nl' => 'Math']]);
        foreach (['needle', 'other'] as $code) {
            Lesson::create(['code' => $code, 'name' => ['nl' => $code], 'status' => StatusEnum::ACTIVE]);
            Topic::create(['lesson_id' => $lesson->id, 'code' => $code, 'title' => ['nl' => $code], 'status' => StatusEnum::ACTIVE]);
            Exam::create(['code' => $code, 'name' => $code, 'locale' => 'nl', 'status' => StatusEnum::ACTIVE]);
            $test = Test::create(['code' => $code, 'name' => $code, 'locale' => 'nl', 'status' => StatusEnum::ACTIVE, 'created_by' => 1]);
            $section = $test->sections()->create(['parent_id' => 0, 'name' => 'Section', 'type' => 'topic']);
            $test->questions()->create(['section_id' => $section->id, 'question_id' => 1, 'lesson_id' => $lesson->id, 'topic_id' => 1]);
        }
        $list = new $class;
        if ($list instanceof ListTopics) {
            $list->lesson = $lesson;
        }
        $list->setPage(1);
        Paginator::currentPageResolver(fn () => $list->getPage());
        $list->word = 'needle';
        $this->assertGreaterThan(1, $list->$query()->total());
        $list->$search();
        $this->assertSame(['needle'], collect($list->$query()->items())->pluck('code')->all());
        $list->clearSearch();
        $this->assertGreaterThan(1, $list->$query()->total());
    }

    public function test_lesson_code_search_excludes_inactive_matches(): void
    {
        Lesson::create(['code' => 'needle-passive', 'name' => ['nl' => 'Hidden'], 'status' => StatusEnum::PASSIVE]);
        Lesson::create(['code' => 'needle-active', 'name' => ['nl' => 'Visible'], 'status' => StatusEnum::ACTIVE]);
        $list = new ListLessons;
        $list->word = 'needle';
        $list->searchLesson();
        $this->assertSame(['needle-active'], collect($list->lessons()->items())->pluck('code')->all());
    }

    public function test_blog_cache_keeps_each_page_separate(): void
    {
        foreach (range(1, 19) as $number) {
            Blog::create(['locale' => app()->getLocale(), 'slug' => "cache-blog-$number", 'title' => "Blog $number",
                'content' => 'Article', 'status' => StatusEnum::ACTIVE, 'published_at' => now()->subMinutes($number)]);
        }
        $list = new ListBlogs;
        $list->setPage(1);
        Paginator::currentPageResolver(fn () => $list->getPage());
        $list->blogs();
        $list->setPage(2);
        $this->assertSame(2, $list->blogs()->currentPage());
        $this->assertCount(1, $list->blogs()->items());
        $list->search = 'Blog 19';
        $list->setPage(1);
        $this->assertSame(['cache-blog-19'], collect($list->blogs()->items())->pluck('slug')->all());
        $list->search = 'no-such-article';
        $this->assertSame(0, $list->blogs()->total());
    }

    public function test_league_cache_respects_the_requested_user_count(): void
    {
        $league = League::create(['code' => 'cache-league', 'label' => 'League', 'start_at' => now()->subDay(), 'end_at' => now()->addDay()]);
        foreach (range(1, 3) as $position) {
            $league->results()->create(['league_number' => 1, 'user_id' => User::factory()->create()->id,
                'position' => $position, 'rank_position' => $position]);
        }
        $list = new LeagueResultTable;
        $list->mount($league, 'League', userCount: 1);
        $this->assertCount(1, $list->results());
        $list->userCount = 3;
        $this->assertCount(3, $list->results());
    }

    public function test_global_reset_preserves_public_content_and_security_stores(): void
    {
        // Only isolated array data is invalidated; compiled files must remain intact.
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('cache.stores.security.driver'));
        Lesson::create(['code' => 'cache-query', 'name' => ['nl' => 'Math'], 'status' => StatusEnum::ACTIVE]);
        Artisan::swap(\Mockery::spy(Kernel::class));
        Cache::put('unrelated-screen', 'cached', 3600);
        Cache::store('security')->put('auth-limit-marker', 5, 60);
        $list = new ListLessons;
        DB::enableQueryLog();
        DB::flushQueryLog();
        $list->lessons();
        $cold = count(DB::getQueryLog());
        DB::flushQueryLog();
        $list->lessons();
        $this->assertCount(0, DB::getQueryLog());
        resetCache();
        Artisan::shouldNotHaveReceived('call');
        $this->assertNull(Cache::get('unrelated-screen'));
        $this->assertSame(5, Cache::store('security')->get('auth-limit-marker'));
        DB::flushQueryLog();
        $list->lessons();
        $this->assertGreaterThan(0, $cold);
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
    }
}
