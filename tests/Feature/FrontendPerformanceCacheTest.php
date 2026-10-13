<?php

namespace Tests\Feature;

use App\Actions\Contacts\CreateMessageAction;
use App\Actions\Contacts\CreateNewsLetterAction;
use App\Actions\Exams\UpdateExamQuestionsAction;
use App\Actions\Pages\UpdateHomePageAction;
use App\Actions\Settings\SettingSaveAction;
use App\Enums\PageMenuEnum;
use App\Enums\PageTypeEnum;
use App\Enums\StatusEnum;
use App\Helpers\FrontendCache;
use App\Livewire\Frontend\Blogs\ListBlogs;
use App\Livewire\Frontend\Contacts\ContactForm;
use App\Livewire\Frontend\Lessons\ListLessons;
use App\Livewire\Frontend\Lessons\ListTopics;
use App\Livewire\Frontend\Newsletter\NewsletterForm;
use App\Livewire\Frontend\Tests\ListTests;
use App\Models\Blog;
use App\Models\Contact;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Newsletter;
use App\Models\Page;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FrontendPerformanceCacheTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('nl');
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
    }

    public function test_public_cache_lasts_seven_days_and_separates_locale_and_revision(): void
    {
        $calls = 0;
        $load = function () use (&$calls) {
            return ++$calls;
        };
        $this->assertSame(604800, config('cache.frontend_ttl'));
        $this->assertSame(1, FrontendCache::remember('blogs', [], $load));
        $this->travel(6)->days();
        $this->assertSame(1, FrontendCache::remember('blogs', [], $load));
        $this->travel(2)->days();
        $this->assertSame(2, FrontendCache::remember('blogs', [], $load));
        app()->setLocale('en');
        $this->assertSame(3, FrontendCache::remember('blogs', [], $load));
        FrontendCache::invalidate('blogs');
        $this->assertSame(4, FrontendCache::remember('blogs', [], $load));
        app()->setLocale('nl');
        $this->assertSame(5, FrontendCache::remember('blogs', [], $load));
    }

    public function test_file_cache_creates_missing_directories_and_survives_default_cache_clear(): void
    {
        $path = sys_get_temp_dir().'/eduan-front-cache-'.bin2hex(random_bytes(6));
        config(['cache.stores.frontend' => ['driver' => 'file', 'path' => $path, 'lock_path' => $path]]);
        Cache::forgetDriver('frontend');
        $this->assertDirectoryDoesNotExist($path);
        $this->assertSame('public', FrontendCache::remember('lessons', [], fn () => 'public'));
        Cache::flush(); // PHPUnit's isolated array default store, never the running application's files.
        $this->assertDirectoryExists($path);
        $this->assertSame('public', FrontendCache::remember('lessons', [], fn () => 'changed'));
    }

    public function test_cold_and_warm_lessons_use_distinct_sql_counts_without_loading_all_tests(): void
    {
        $lesson = Lesson::create(['code' => 'perf-lesson', 'name' => ['nl' => 'Performance'], 'status' => StatusEnum::ACTIVE]);
        $topic = Topic::create(['lesson_id' => $lesson->id, 'code' => 'perf-topic', 'title' => ['nl' => 'Topic'], 'status' => StatusEnum::ACTIVE]);
        $exam = Exam::create(['code' => 'perf-exam', 'name' => 'Test', 'locale' => 'nl', 'status' => StatusEnum::ACTIVE]);
        foreach ([1, 2] as $question) {
            DB::table('exams_questions')->insert(['exam_id' => $exam->id, 'question_id' => $question, 'lesson_id' => $lesson->id, 'topic_id' => $topic->id]);
        }
        $list = new ListLessons;
        $list->paginate = 100;
        Paginator::currentPageResolver(fn () => 1);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $item = $list->lessons()->getCollection()->firstWhere('id', $lesson->id);
        $this->assertCount(2, DB::getQueryLog());
        $this->assertSame(1, $item->exams_count);
        $this->assertSame(1, $item->topics_count);
        $this->assertFalse($item->relationLoaded('exams'));
        DB::flushQueryLog();
        $list->lessons();
        $this->assertCount(0, DB::getQueryLog());
        $topics = new ListTopics;
        $topics->lesson = $lesson;
        $entry = $topics->topics()->first();
        $this->assertTrue($entry->relationLoaded('lesson'));
        $this->assertSame(1, $entry->exams_count);
        DB::disableQueryLog();
    }

    public function test_edit_invalidates_content_but_view_counter_does_not_and_delete_still_does(): void
    {
        $lesson = Lesson::create(['code' => 'perf-change', 'name' => ['nl' => 'Before'], 'status' => StatusEnum::ACTIVE]);
        $load = fn () => Lesson::find($lesson->id)?->name;
        $this->assertSame('Before', FrontendCache::remember('lessons', ['record' => $lesson->id], $load));
        $before = Cache::store('frontend')->get('revision:lessons');
        $lesson->increment('hits');
        $this->assertSame($before, Cache::store('frontend')->get('revision:lessons'));
        $lesson->update(['name' => ['nl' => 'After']]);
        $this->assertSame('After', FrontendCache::remember('lessons', ['record' => $lesson->id], $load));
        $lesson->increment('hits');
        $lesson->delete();
        $this->assertNull(FrontendCache::remember('lessons', ['record' => $lesson->id], $load));
    }

    public function test_test_catalog_cache_never_contains_another_students_results(): void
    {
        $exam = Exam::create(['code' => 'perf-personal', 'name' => 'Personal test', 'locale' => 'nl', 'status' => StatusEnum::ACTIVE, 'hits' => 999999]);
        $first = User::factory()->create();
        $second = User::factory()->create();
        foreach ([$first, $second] as $index => $user) {
            $result = ExamResult::create(['user_id' => $user->id, 'exam_id' => $exam->id, 'completed' => 0]);
            $result->details()->create(['question_id' => 1, 'answer_id' => 1, 'correct' => $index === 0 ? 1 : 0, 'time' => 1]);
        }
        $list = new ListTests;
        Paginator::currentPageResolver(fn () => 1);
        $this->actingAs($first);
        $item = $list->tests()->firstWhere('id', $exam->id);
        $this->assertSame(1, $item->personal_correct_count);
        $this->assertSame(0, $item->personal_incorrect_count);
        $this->assertSame(1, $item->personal_results_count);
        $this->actingAs($second);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $other = $list->tests()->firstWhere('id', $exam->id);
        $this->assertCount(2, DB::getQueryLog());
        $this->assertSame(0, $other->personal_correct_count);
        $this->assertSame(1, $other->personal_incorrect_count);
        $this->assertFalse($other->relationLoaded('userResults'));
        auth()->logout();
        DB::flushQueryLog();
        $guest = $list->tests()->firstWhere('id', $exam->id);
        $this->assertCount(0, DB::getQueryLog());
        $this->assertArrayNotHasKey('personal_correct_count', $guest->getAttributes());
        DB::disableQueryLog();
    }

    public function test_scheduled_blog_becomes_visible_at_its_publication_time_and_content_is_not_cached(): void
    {
        $blog = Blog::create(['locale' => 'nl', 'slug' => 'perf-scheduled', 'title' => 'Scheduled', 'content' => str_repeat('large', 1000), 'status' => StatusEnum::ACTIVE, 'published_at' => now()->addHour()]);
        $list = new ListBlogs;
        Paginator::currentPageResolver(fn () => 1);
        $this->assertNull($list->blogs()->firstWhere('id', $blog->id));
        $this->travel(61)->minutes();
        $visible = $list->blogs()->firstWhere('id', $blog->id);
        $this->assertNotNull($visible);
        $this->assertArrayNotHasKey('content', $visible->getAttributes());
    }

    public function test_public_submissions_preserve_public_cache_and_refresh_only_the_message_badge(): void
    {
        Artisan::swap(\Mockery::spy(Kernel::class));
        FrontendCache::remember('lessons', [], fn () => 'public');
        Cache::put('unrelated-screen', 'keep');
        Cache::put('data_countContactMessageNotRead', 3);
        CreateMessageAction::run(['name' => 'QA', 'email' => 'qa@example.invalid', 'message' => 'Testing only', 'accept_terms' => true]);
        CreateNewsLetterAction::run(['email' => 'qa-cache@example.invalid', 'accept_terms' => true]);
        $this->assertNull(Cache::get('data_countContactMessageNotRead'));
        $this->assertSame('keep', Cache::get('unrelated-screen'));
        $this->assertSame('public', FrontendCache::remember('lessons', [], fn () => 'new'));
        Artisan::shouldNotHaveReceived('call');
    }

    public function test_home_page_bulk_save_and_test_question_sync_invalidate_their_public_snapshots(): void
    {
        $page = Page::create(['slug' => 'perf-home', 'title' => ['nl' => 'Before'], 'menu' => PageMenuEnum::HOME, 'type' => PageTypeEnum::SYSTEM]);
        FrontendCache::remember('pages', [], fn () => 'old');
        UpdateHomePageAction::run(['title' => ['nl' => 'After']]);
        $this->assertSame('new', FrontendCache::remember('pages', [], fn () => 'new'));
        $this->assertSame('After', $page->fresh()->title);
        $exam = Exam::create(['code' => 'perf-pivot', 'name' => 'Test', 'locale' => 'nl']);
        FrontendCache::remember('tests', [], fn () => 'old');
        UpdateExamQuestionsAction::run($exam, []);
        $this->assertSame('new', FrontendCache::remember('tests', [], fn () => 'new'));
    }

    public function test_home_primary_sections_are_present_in_the_initial_html(): void
    {
        $content = ['lessonStatus' => 'active', 'lessonTitle' => 'Primary lessons QA', 'lessonCount' => 3,
            'testStatus' => 'active', 'testTitle' => 'Primary tests QA', 'testCount' => 3,
            'blogStatus' => 'active', 'blogTitle' => 'Primary blogs QA', 'blogCount' => 3,
            'faqStatus' => 'active', 'faqTitle' => 'Primary FAQs QA', 'faqCount' => 3];
        $page = Page::where('menu', PageMenuEnum::HOME)->first();
        if (! $page) {
            $page = Page::create(['slug' => 'perf-primary', 'menu' => PageMenuEnum::HOME, 'type' => PageTypeEnum::SYSTEM]);
        }
        $page->update(['content' => ['nl' => $content]]);
        $response = $this->get(route('frontend.home'))->assertOk();
        foreach (['Primary lessons QA', 'Primary tests QA', 'Primary blogs QA', 'Primary FAQs QA'] as $heading) {
            $response->assertSee($heading);
        }
        $response->assertDontSee('&quot;lazyLoaded&quot;:false', false);
    }

    public function test_contact_and_newsletter_keep_validation_without_creating_invalid_records(): void
    {
        $contacts = Contact::count();
        $newsletters = Newsletter::count();
        Livewire::test(ContactForm::class)
            ->set('email', 'invalid')->set('acceptTerms', false)->call('submit')
            ->assertHasErrors(['name', 'email', 'schoolName', 'message', 'acceptTerms']);
        Livewire::test(NewsletterForm::class)
            ->set('email', 'invalid')->set('acceptTerms', false)->call('submit')
            ->assertHasErrors(['email', 'acceptTerms']);
        $this->assertSame($contacts, Contact::count());
        $this->assertSame($newsletters, Newsletter::count());
    }

    public function test_settings_save_refreshes_localized_public_text_without_caching_the_settings_object(): void
    {
        settings()->set('siteTitle_nl', 'Before settings QA');
        settings()->save();
        $this->assertSame('Before settings QA', data()->setting('siteTitle', 'nl'));
        $user = User::factory()->create();
        foreach (['dashboard:access', 'settings:update'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo(['dashboard:access', 'settings:update']);
        $request = Request::create('/admin/settings', 'POST', ['settings' => ['siteTitle_nl' => 'After settings QA']]);
        $request->setUserResolver(fn () => $user);
        SettingSaveAction::run($request);
        $this->assertSame('After settings QA', data()->setting('siteTitle', 'nl'));
        $this->assertIsString(data()->setting('siteTitle', 'nl'));
    }

    public function test_search_and_deep_pages_do_not_grow_the_seven_day_cache_and_paths_follow_current_page(): void
    {
        $calls = 0;
        $load = function () use (&$calls) {
            $calls++;

            return new LengthAwarePaginator([], 0, 21);
        };
        foreach ([['search' => 'word'], ['page' => 101]] as $context) {
            FrontendCache::paginate('lessons', $context, $load);
            FrontendCache::paginate('lessons', $context, $load);
        }
        $this->assertSame(4, $calls);
        Paginator::currentPathResolver(fn () => '/lessons');
        $this->assertSame('/lessons', FrontendCache::paginate('lessons', [], $load)->path());
        Paginator::currentPathResolver(fn () => '/');
        $this->assertSame('/', FrontendCache::paginate('lessons', [], $load)->path());
        $this->assertSame(5, $calls);
    }
}
