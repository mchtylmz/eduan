<?php

namespace Tests\Feature;

use App\Enums\PageMenuEnum;
use App\Enums\PageTypeEnum;
use App\Enums\StatusEnum;
use App\Helpers\FrontendSeo;
use App\Helpers\SeoSitemap;
use App\Models\Blog;
use App\Models\Exam;
use App\Models\Language;
use App\Models\League;
use App\Models\Lesson;
use App\Models\Page;
use App\Models\Test;
use App\Models\Topic;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FrontendSeoUpgradeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['nl', 'en', 'tr'] as $locale) {
            Language::firstOrCreate(['code' => $locale], ['name' => $locale, 'status' => StatusEnum::ACTIVE]);
        }
    }

    private function html(string $body): \DOMXPath
    {
        $document = new \DOMDocument;
        @$document->loadHTML($body);

        return new \DOMXPath($document);
    }

    private function graph(\DOMXPath $xpath): array
    {
        return json_decode($xpath->query('//script[@type="application/ld+json"]')->item(0)->textContent, true, flags: JSON_THROW_ON_ERROR)['@graph'];
    }

    public static function publicPages(): array
    {
        return array_map(fn ($route) => [$route], ['home', 'lessons', 'tests', 'exams', 'blog', 'faqs', 'contact', 'leagues']);
    }

    #[DataProvider('publicPages')]
    public function test_public_pages_have_one_metadata_set_main_heading_and_valid_structured_data(string $route): void
    {
        $response = $this->get(route('frontend.'.$route))->assertOk();
        $html = $this->html($response->getContent());
        foreach (['//title', '//meta[@name="description"]', '//link[@rel="canonical"]', '//meta[@name="robots"]', '//meta[@property="og:title"]'] as $selector) {
            $this->assertSame(1, $html->query($selector)->length, $route.' '.$selector);
            $this->assertNotEmpty($html->query($selector)->item(0)->textContent ?: $html->query($selector)->item(0)->getAttribute('content') ?: $html->query($selector)->item(0)->getAttribute('href'));
        }
        $this->assertSame(1, $html->query('//h1')->length, $route.' main heading');
        $graph = $this->graph($html);
        $this->assertContains('WebSite', array_column($graph, '@type'));
        $this->assertContains('EducationalOrganization', array_column($graph, '@type'));
        $this->assertStringContainsString('index, follow', $html->query('//meta[@name="robots"]')->item(0)->getAttribute('content'));
    }

    public function test_canonical_drops_tracking_preserves_pagination_and_language_and_hreflang_is_reciprocal(): void
    {
        foreach (['nl', 'en'] as $locale) {
            $response = $this->get(route('frontend.lessons').'?lang='.$locale.'&page=2&utm_source=tracking')->assertOk();
            $html = $this->html($response->getContent());
            $this->assertSame(FrontendSeo::localizedUrl(route('frontend.lessons'), $locale, 2), $html->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
            foreach (['nl', 'en', 'tr'] as $code) {
                $this->assertSame(FrontendSeo::localizedUrl(route('frontend.lessons'), $code, 2), $html->query('//link[@hreflang="'.$code.'"]')->item(0)->getAttribute('href'));
            }
        }
    }

    public function test_article_has_plain_description_safe_json_and_only_its_actual_content_language(): void
    {
        $blog = Blog::create(['locale' => 'nl', 'slug' => 'seo-article', 'title' => 'Article </script><script>alert(1)</script>', 'brief' => '<b>Useful article</b>', 'content' => '<p>Readable educational article</p>', 'status' => StatusEnum::ACTIVE, 'published_at' => now()->subDay()]);
        $modified = $blog->updated_at->toIso8601String();
        $response = $this->get(route('frontend.blog.detail', $blog->slug))->assertOk();
        $html = $this->html($response->getContent());
        $this->assertSame('Useful article', $html->query('//meta[@name="description"]')->item(0)->getAttribute('content'));
        $this->assertSame(1, $html->query('//h1')->length);
        $this->assertSame(0, $html->query('//link[@hreflang="en"]')->length);
        $article = collect($this->graph($html))->firstWhere('@type', 'BlogPosting');
        $this->assertSame($modified, $article['dateModified']);
        $this->assertSame('nl', $article['inLanguage']);
        $this->assertStringNotContainsString('</script>', json_encode($article['headline']));
        $this->assertSame($blog->hits + 1, $blog->fresh()->hits);
    }

    public function test_sitemap_has_public_lists_only_published_details_and_warm_cache_without_queries(): void
    {
        $blog = Blog::create(['locale' => 'en', 'slug' => 'seo-future', 'title' => 'Future', 'status' => StatusEnum::ACTIVE, 'published_at' => now()->addDay()]);
        $lesson = Lesson::create(['code' => 'seo-lesson', 'name' => ['nl' => 'Math'], 'status' => StatusEnum::ACTIVE]);
        Topic::create(['lesson_id' => $lesson->id, 'code' => 'seo-topic', 'title' => ['nl' => 'Math topic'], 'status' => StatusEnum::ACTIVE]);
        $response = $this->get(route('frontend.sitemap'))->assertOk()->assertHeader('Content-Type', 'text/xml; charset=utf-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $locations = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));
        foreach (['tests', 'exams', 'lessons', 'home'] as $route) {
            $this->assertContains(FrontendSeo::localizedUrl(route('frontend.'.$route), 'nl'), $locations);
        }
        $this->assertNotContains(route('frontend.blog.detail', $blog->slug).'?lang=en', $locations);
        $this->assertNotContains(route('login'), $locations);
        DB::enableQueryLog();
        DB::flushQueryLog();
        SeoSitemap::entries();
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        $blog->update(['published_at' => now()->subMinute()]);
        $this->assertContains(FrontendSeo::localizedUrl(route('frontend.blog.detail', $blog->slug), 'en'), array_column(SeoSitemap::entries(), 'loc'));
    }

    public function test_disabled_or_unpublished_details_return_404_instead_of_indexable_content(): void
    {
        $blog = Blog::create(['locale' => 'nl', 'slug' => 'seo-not-yet', 'title' => 'Future', 'status' => StatusEnum::ACTIVE, 'published_at' => now()->addDay()]);
        $lesson = Lesson::create(['code' => 'seo-disabled-lesson', 'name' => ['nl' => 'Hidden'], 'status' => StatusEnum::PASSIVE]);
        $exam = Exam::create(['locale' => 'nl', 'code' => 'seo-disabled-test', 'name' => 'Hidden', 'status' => StatusEnum::PASSIVE]);
        $test = Test::create(['locale' => 'nl', 'code' => 'seo-disabled-exam', 'name' => 'Hidden', 'status' => StatusEnum::PASSIVE, 'created_by' => 1]);
        foreach ([['blog.detail', $blog->slug], ['lesson', $lesson->code], ['test.detail', $exam->code]] as [$route, $code]) {
            $this->get(route('frontend.'.$route, $code))->assertNotFound();
        }
        $this->get(route('frontend.exam.detail', $test->code))->assertOk()->assertSee(__('learning.coming_soon'));
    }

    public function test_auth_search_and_personal_pages_remain_noindex_and_search_has_no_graph(): void
    {
        foreach ([route('login'), route('frontend.register'), route('frontend.recover.password'), route('frontend.search')] as $url) {
            $response = $this->get($url)->assertOk();
            $html = $this->html($response->getContent());
            $this->assertSame('noindex, nofollow', $html->query('//meta[@name="robots"]')->item(0)->getAttribute('content'));
            $this->assertSame(0, $html->query('//script[@type="application/ld+json"]')->length);
        }
        $this->get(route('frontend.profile'))->assertRedirect(route('login'));
    }

    public function test_learning_details_and_topic_titles_have_specific_metadata_without_exposing_answers(): void
    {
        $exam = Exam::create(['locale' => 'nl', 'code' => 'seo-visible-test', 'name' => 'Algebra QA', 'content' => '<p>Practice algebra expressions.</p>', 'status' => StatusEnum::ACTIVE]);
        $test = Test::create(['locale' => 'nl', 'code' => 'seo-visible-exam', 'name' => 'Timed algebra QA', 'content' => '<p>Timed algebra exam.</p>', 'status' => StatusEnum::ACTIVE, 'created_by' => 1]);
        foreach ([['test.detail', $exam->code], ['exam.detail', $test->code]] as [$route, $code]) {
            $html = $this->html($this->get(route('frontend.'.$route, $code))->assertOk()->getContent());
            $this->assertSame(1, $html->query('//h1')->length);
            $resource = collect($this->graph($html))->firstWhere('@type', 'LearningResource');
            $this->assertNotEmpty($resource['description']);
            $this->assertArrayNotHasKey('acceptedAnswer', $resource);
            $this->assertArrayNotHasKey('aggregateRating', $resource);
        }
        $lesson = Lesson::create(['code' => 'seo-topic-parent', 'name' => ['nl' => 'Mathematics'], 'status' => StatusEnum::ACTIVE]);
        $topic = Topic::create(['lesson_id' => $lesson->id, 'code' => 'seo-topic-title', 'title' => ['nl' => 'Polynomials QA'], 'description' => ['nl' => 'Polynomial practice.'], 'status' => StatusEnum::ACTIVE]);
        $html = $this->html($this->get(route('frontend.tests', $topic->code).'?lang=nl')->assertOk()->getContent());
        $this->assertStringContainsString('Polynomials QA', $html->query('//title')->item(0)->textContent);
        $html = $this->html($this->get(route('frontend.tests', $topic->code).'?lang=en')->assertOk()->getContent());
        $this->assertSame('noindex, nofollow', $html->query('//meta[@name="robots"]')->item(0)->getAttribute('content'));
        $this->assertSame(0, $html->query('//link[@hreflang="en"]')->length);
        $this->assertSame(FrontendSeo::localizedUrl(route('frontend.tests', $topic->code), 'nl'), $html->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
    }

    public function test_sitemap_publication_deadline_and_content_only_modified_date_are_preserved(): void
    {
        $blog = Blog::create(['locale' => 'nl', 'slug' => 'seo-publish-deadline', 'title' => 'Soon', 'status' => StatusEnum::ACTIVE, 'published_at' => now()->addHour()]);
        $url = FrontendSeo::localizedUrl(route('frontend.blog.detail', $blog->slug), 'nl');
        $this->assertNotContains($url, array_column(SeoSitemap::entries(), 'loc'));
        $this->travel(61)->minutes();
        $this->assertContains($url, array_column(SeoSitemap::entries(), 'loc'));
        $modified = $blog->updated_at;
        incrementIf(true, $blog);
        $this->assertTrue($modified->equalTo($blog->fresh()->updated_at));
        $blog->update(['brief' => 'Content really changed']);
        $this->assertTrue($blog->fresh()->updated_at->greaterThan($modified));
    }

    public function test_canonical_uses_configured_origin_and_robots_does_not_block_rendering_assets(): void
    {
        $this->assertSame(rtrim(config('app.url'), '/').'/lessons?lang=nl', FrontendSeo::localizedUrl('https://alias.invalid/lessons', 'nl'));
        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('Sitemap: https://hypotenuse.be/sitemap.xml', $robots);
        $this->assertStringContainsString('Disallow: /account/', $robots);
        $this->assertStringNotContainsString('Disallow: /assets/', $robots);
        $this->assertStringNotContainsString('Disallow: /livewire', $robots);
    }

    public function test_lesson_custom_page_and_league_details_have_one_heading_and_real_sitemap_urls(): void
    {
        $lesson = Lesson::create(['code' => 'seo-full-lesson', 'name' => ['nl' => 'Geometry QA'], 'description' => ['nl' => 'Geometry lessons.'], 'status' => StatusEnum::ACTIVE]);
        $page = Page::create(['slug' => 'seo-footer-page', 'title' => ['nl' => 'About QA'], 'brief' => ['nl' => 'Our teaching approach.'], 'content' => ['nl' => '<p>Teaching mathematics.</p>'], 'menu' => PageMenuEnum::FOOTER, 'type' => PageTypeEnum::CUSTOM, 'status' => StatusEnum::ACTIVE]);
        $redirect = Page::create(['slug' => 'seo-footer-redirect', 'title' => ['nl' => 'External QA'], 'link' => ['nl' => 'https://example.org'], 'menu' => PageMenuEnum::FOOTER, 'type' => PageTypeEnum::CUSTOM, 'status' => StatusEnum::ACTIVE]);
        $league = League::create(['code' => 'seo-league', 'label' => 'QA league period', 'start_at' => now()->subWeek(), 'end_at' => now()]);
        foreach ([['lesson', $lesson->code, 'Geometry QA'], ['page', $page->slug, 'About QA'], ['leagues.detail', $league->code, __('Lig Sonuçları', [], 'nl')]] as [$route, $code, $title]) {
            $html = $this->html($this->get(route('frontend.'.$route, $code))->assertOk()->getContent());
            $this->assertSame(1, $html->query('//h1')->length);
            $this->assertStringContainsString($title, $html->query('//title')->item(0)->textContent);
            if ($route === 'leagues.detail') {
                $this->assertStringContainsString(dateFormat($league->start_at, 'd M Y').' – '.dateFormat($league->end_at, 'd M Y'), $html->query('//title')->item(0)->textContent);
                $this->assertStringNotContainsString($league->label, $html->query('//title')->item(0)->textContent);
            }
            $this->assertContains('BreadcrumbList', array_column($this->graph($html), '@type'));
        }
        $locations = array_column(SeoSitemap::entries(), 'loc');
        $this->assertContains(FrontendSeo::localizedUrl(route('frontend.page', $page->slug), 'nl'), $locations);
        $this->assertNotContains(FrontendSeo::localizedUrl(route('frontend.page', $redirect->slug), 'nl'), $locations);
    }

    public function test_default_language_alternate_overrides_a_previous_english_session(): void
    {
        $this->withSession(['locale' => 'en']);
        $html = $this->html($this->get(FrontendSeo::localizedUrl(route('frontend.lessons'), 'nl'))->assertOk()->getContent());
        $this->assertSame('nl', $html->query('//html')->item(0)->getAttribute('lang'));
        $this->assertSame(FrontendSeo::localizedUrl(route('frontend.lessons'), 'nl'), $html->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
    }

    public function test_token_route_metadata_omits_the_private_url(): void
    {
        Route::get('/seo-token-check/{token}', fn () => FrontendSeo::metadata('Password recovery'))->name('frontend.create.password');
        $response = $this->get('/seo-token-check/private-token-value')->assertOk();
        $response->assertJsonPath('canonical', null)->assertJsonPath('robots', 'noindex, nofollow')->assertJsonPath('alternates', []);
    }
}
