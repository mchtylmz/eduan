<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Enums\VisibilityEnum;
use App\Helpers\FrontendSeo;
use App\Models\Exam;
use App\Models\Test;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FrontendSeoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_is_not_indexable_and_has_a_real_canonical_link(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow"', false)
            ->assertSee('<link rel="canonical" href="'.route('login').'"', false)
            ->assertSee('<meta property="og:url" content="'.route('login').'"', false);
    }

    public function test_sitemap_uses_detail_urls_and_excludes_inactive_tests(): void
    {
        foreach ([StatusEnum::ACTIVE, StatusEnum::PASSIVE] as $status) {
            Exam::create([
                'locale' => 'nl', 'name' => 'Practice', 'code' => 'practice-'.$status->value,
                'status' => $status, 'visibility' => VisibilityEnum::LOGGED,
            ]);
            Test::create([
                'locale' => 'nl', 'name' => 'Timed', 'code' => 'timed-'.$status->value,
                'status' => $status, 'created_by' => 1,
            ]);
        }
        $response = $this->get(route('frontend.sitemap'))->assertOk()
            ->assertSee(route('frontend.test.detail', 'practice-active'), false)
            ->assertSee(route('frontend.exam.detail', 'timed-active'), false)
            ->assertDontSee('practice-passive')->assertDontSee('timed-passive');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame(1, substr_count($response->getContent(), '<loc>'.FrontendSeo::localizedUrl(route('frontend.home'), 'nl').'</loc>'));
    }
}
