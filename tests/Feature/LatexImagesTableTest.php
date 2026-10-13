<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Livewire\Ai\LatexImageForm;
use App\Livewire\Ai\LatexImagesTable;
use App\Models\Language;
use App\Models\LatexImage;
use App\Models\User;
use App\Observers\TableCacheObserver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LatexImagesTableTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('nl');
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        $this->actor = User::factory()->create();
        foreach (['dashboard:access', 'ai:view', 'ai:update', 'ai:delete'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $this->actor->givePermissionTo(['dashboard:access', 'ai:view']);
        $this->actingAs($this->actor);
    }

    public function test_page_and_json_require_current_dashboard_and_ai_permission(): void
    {
        $this->get(route('admin.ai.images'))->assertOk()
            ->assertSee('ai-latex-images-table', false)->assertDontSee('wire:lazy', false)
            ->assertSee('data-pagination-parts=\'["pageInfo","pageList"]\'', false);
        $this->getJson(route('admin.ai.getImagesData'))->assertOk()->assertJsonStructure(['total', 'rows']);
        $this->actor->revokePermissionTo('ai:view');
        $this->getJson(route('admin.ai.getImagesData'))->assertForbidden();
        Livewire::test(LatexImagesTable::class)->assertForbidden();
        $this->actor->givePermissionTo('ai:view');
        $this->actor->revokePermissionTo('dashboard:access');
        $this->getJson(route('admin.ai.getImagesData'))->assertForbidden();
        auth()->logout();
        $this->getJson(route('admin.ai.getImagesData'))->assertUnauthorized();
    }

    public function test_json_search_sort_pagination_and_actions_preserve_existing_fields(): void
    {
        $prefix = 'latex-'.fake()->uuid();
        $first = LatexImage::create(['formula' => $prefix.' A <script>', 'image' => 'uploads/latex/'.$prefix.'-z.svg']);
        $second = LatexImage::create(['formula' => $prefix.' Z', 'image' => 'uploads/latex/'.$prefix.'-a.svg']);
        $parameters = ['search' => $prefix, 'limit' => 1, 'sort' => 'formula', 'order' => 'asc'];
        $this->getJson(route('admin.ai.getImagesData', $parameters))->assertOk()
            ->assertJsonPath('total', 2)->assertJsonPath('rows.0.id', $first->id)
            ->assertJsonPath('rows.0.formula', $first->formula)->assertJsonPath('rows.0.edit_url', null)
            ->assertJsonPath('rows.0.can_delete', false)->assertJsonPath('rows.0.hide_detail', true)
            ->assertJsonMissingPath('rows.0.content');
        $this->getJson(route('admin.ai.getImagesData', [...$parameters, 'offset' => 1]))->assertJsonPath('rows.0.id', $second->id);
        $this->getJson(route('admin.ai.getImagesData', ['search' => $prefix.'-a.svg']))->assertJsonPath('total', 1);
        $this->getJson(route('admin.ai.getImagesData', [...$parameters, 'sort' => 'image']))->assertJsonPath('rows.0.id', $second->id);
        $this->getJson(route('admin.ai.getImagesData', ['search' => $prefix.'-missing']))->assertJson(['total' => 0, 'rows' => []]);
        foreach ([['sort' => 'image;DROP'], ['limit' => 101], ['offset' => -1], ['status' => 'active']] as $invalid) {
            $this->getJson(route('admin.ai.getImagesData', $invalid))->assertUnprocessable();
        }
        $this->actor->givePermissionTo(['ai:update', 'ai:delete']);
        $this->getJson(route('admin.ai.getImagesData', $parameters))->assertJsonPath('rows.0.edit_url', route('admin.ai.imageForm', $first))
            ->assertJsonPath('rows.0.can_delete', true);
        $this->get(route('admin.ai.imageForm', $first))->assertOk()->assertSee('wire:submit="save"', false);
    }

    public function test_cache_refresh_and_targeted_model_invalidation(): void
    {
        $prefix = 'latex-'.fake()->uuid();
        $record = LatexImage::create(['formula' => $prefix.' initial', 'image' => 'uploads/latex/a.svg']);
        $parameters = ['search' => $prefix];
        $this->getJson(route('admin.ai.getImagesData', $parameters))->assertJsonPath('rows.0.formula', $prefix.' initial');
        DB::table('latex_images')->where('id', $record->id)->update(['formula' => $prefix.' raw']);
        $this->getJson(route('admin.ai.getImagesData', $parameters))->assertJsonPath('rows.0.formula', $prefix.' initial');
        $this->getJson(route('admin.ai.getImagesData', [...$parameters, 'refresh' => 1]))->assertJsonPath('rows.0.formula', $prefix.' raw');
        $record->update(['formula' => $prefix.' saved']);
        $this->getJson(route('admin.ai.getImagesData', $parameters))->assertJsonPath('rows.0.formula', $prefix.' saved');
        Cache::put('phase60-unrelated-marker', 'keep', 60);
        $revision = Cache::get('admin-table:revision:ai-latex-images');
        (new TableCacheObserver)->saved($record);
        $this->assertNotSame($revision, Cache::get('admin-table:revision:ai-latex-images'));
        $this->assertSame('keep', Cache::get('phase60-unrelated-marker'));
        $this->assertTrue($record::getEventDispatcher()->hasListeners('eloquent.saved: '.LatexImage::class));
        $this->actor->givePermissionTo('ai:delete');
        Livewire::test(LatexImagesTable::class)->call('delete', $record->id)
            ->assertDispatched('refresh-server-table', table: 'ai-latex-images');
        $this->getJson(route('admin.ai.getImagesData', $parameters))->assertJson(['total' => 0, 'rows' => []]);
    }

    public function test_delete_and_image_update_permissions_are_checked_on_each_action(): void
    {
        $record = LatexImage::create(['formula' => 'latex-'.fake()->uuid(), 'image' => 'uploads/latex/a.svg']);
        Livewire::test(LatexImagesTable::class)->call('delete', $record->id)->assertForbidden();
        $this->assertTrue($record->fresh()->exists);
        Livewire::test(LatexImageForm::class, ['latexImage' => $record])->call('save')->assertForbidden();
        $this->actor->givePermissionTo('ai:delete');
        Livewire::test(LatexImagesTable::class)->call('delete', PHP_INT_MAX)->assertNotFound();
        $component = Livewire::test(LatexImagesTable::class);
        $this->actor->revokePermissionTo('ai:view');
        $component->call('delete', $record->id)->assertForbidden();
        $this->assertTrue($record->fresh()->exists);
    }
}
