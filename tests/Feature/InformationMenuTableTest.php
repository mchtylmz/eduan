<?php

namespace Tests\Feature;

use App\Enums\PageTypeEnum;
use App\Enums\StatusEnum;
use App\Livewire\Pages\PagesTable;
use App\Models\Blog;
use App\Models\Contact;
use App\Models\Exam;
use App\Models\ExamReview;
use App\Models\Faq;
use App\Models\Language;
use App\Models\Newsletter;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InformationMenuTableTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('nl');
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        $names = ['dashboard:access', 'exams:view'];
        foreach (['blogs', 'pages', 'contacts', 'newsletter', 'languages', 'exams-reviews'] as $resource) {
            foreach (['view', 'update', 'delete'] as $operation) {
                $names[] = $resource.':'.$operation;
            }
        }
        $names[] = 'languages:translate';
        foreach ($names as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $this->actor = User::factory()->create();
        $this->actor->givePermissionTo($names);
        $this->actingAs($this->actor)->withSession(['locale' => 'nl']);
    }

    public static function lists(): array
    {
        return [
            ['blogs', 'blogs', 'admin.blogs.getData', 'admin.blogs.index'],
            ['pages', 'pages', 'admin.pages.getData', 'admin.pages.all'],
            ['faqs', 'pages', 'admin.pages.faqs.getData', 'admin.pages.faqs.index'],
            ['contacts', 'contacts', 'admin.contacts.getData', 'admin.contacts.index'],
            ['newsletter', 'newsletter', 'admin.newsletter.getData', 'admin.newsletter.index'],
            ['languages', 'languages', 'admin.languages.getData', 'admin.languages.index'],
            ['reviews', 'exams-reviews', 'admin.exams.getReviewsData', 'admin.exams.reviews'],
        ];
    }

    private function records(string $table, string $prefix): array
    {
        $records = [];
        foreach (['a', 'b'] as $suffix) {
            $title = $prefix.$suffix;
            $records[] = match ($table) {
                'blogs' => Blog::factory()->create(['locale' => 'nl', 'title' => $title]),
                'pages' => Page::create(['slug' => $title, 'title' => ['nl' => $title], 'type' => PageTypeEnum::CUSTOM]),
                'faqs' => Faq::factory()->create(['locale' => 'nl', 'title' => $title]),
                'contacts' => Contact::create(['locale' => 'nl', 'name' => $title, 'email' => $title.'@example.invalid', 'message' => $title, 'has_read' => 1]),
                'newsletter' => Newsletter::create(['email' => $title.'@example.invalid', 'token' => 'private-newsletter']),
                'languages' => Language::create(['code' => Str::lower(Str::random(5)), 'name' => $title, 'status' => 'active']),
                'reviews' => ExamReview::create(['user_id' => $this->actor->id, 'exam_id' => Exam::factory()->create(['locale' => 'nl', 'code' => $title, 'created_by' => $this->actor->id])->id, 'comment' => $title]),
            };
        }

        return $records;
    }

    #[DataProvider('lists')]
    public function test_json_pagination_and_existing_menu_render(string $table, string $resource, string $dataRoute, string $pageRoute): void
    {
        $prefix = 'info-'.Str::lower(Str::random(12));
        [$first, $last] = $this->records($table, $prefix);
        $params = ['search' => $prefix, 'sort' => 'id', 'order' => 'asc', 'limit' => 1];
        $response = $this->getJson(route($dataRoute, $params))->assertOk()->assertJsonPath('total', 2)->assertJsonPath('rows.0.id', $first->id);
        $this->getJson(route($dataRoute, [...$params, 'offset' => 1]))->assertJsonPath('rows.0.id', $last->id);
        $this->get(route($pageRoute))->assertOk()->assertSee($table.'-table');
        $this->assertArrayNotHasKey('token', $response->json('rows.0'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    #[DataProvider('lists')]
    public function test_invalid_sort_and_permission_revocation_reject_cached_rows(string $table, string $resource, string $dataRoute, string $pageRoute): void
    {
        $this->getJson(route($dataRoute, ['sort' => 'invalid; drop']))->assertUnprocessable();
        $this->getJson(route($dataRoute))->assertOk();
        $this->actor->revokePermissionTo($resource.':view');
        $this->getJson(route($dataRoute))->assertForbidden();
        auth()->logout();
        $this->getJson(route($dataRoute))->assertUnauthorized();
    }

    public function test_contact_read_filters_links_and_cache_invalidation(): void
    {
        $prefix = 'info-'.Str::lower(Str::random(12));
        [$first, $last] = $this->records('contacts', $prefix);
        $first->update(['has_read' => 0]);
        $params = ['search' => $prefix, 'locale' => 'nl', 'has_read' => [0]];
        $response = $this->getJson(route('admin.contacts.getData', $params))->assertJsonPath('total', 1)->assertJsonPath('rows.0.can_delete', false);
        $this->assertSame(route('admin.contacts.detail', $first), $response->json('rows.0.view_url'));
        $first->update(['has_read' => 1]);
        $this->getJson(route('admin.contacts.getData', $params))->assertJsonPath('total', 0);
        $this->getJson(route('admin.contacts.getData', [...$params, 'has_read' => [0, 1]]))->assertJsonPath('total', 2);
    }

    public function test_system_page_remains_readonly_and_cannot_be_deleted(): void
    {
        $title = 'info-'.Str::lower(Str::random(12));
        $page = Page::create(['slug' => $title, 'title' => ['nl' => $title], 'type' => PageTypeEnum::SYSTEM]);
        $this->getJson(route('admin.pages.getData', ['search' => $title]))->assertJsonPath('rows.0.edit_url', null)->assertJsonPath('rows.0.can_delete', false);
        Livewire::test(PagesTable::class)->call('delete', $page->id);
        $this->assertModelExists($page);
    }
}
