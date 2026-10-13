<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Livewire\Blogs\BlogForm;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Livewire\Exceptions\PayloadTooLargeException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EditorPayloadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_existing_large_blog_can_update_over_the_real_livewire_endpoint(): void
    {
        $actor = User::factory()->create();
        foreach (['dashboard:access', 'blogs:view', 'blogs:update'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $actor->givePermissionTo($permission);
        }
        $this->actingAs($actor);
        $content = '<p>Existing</p><img src="data:image/png;base64,'.str_repeat('A', 6780000).'">';
        $blog = Blog::create([
            'locale' => 'nl', 'slug' => 'editor-payload-test', 'title' => 'Large editor',
            'content' => htmlentities($content), 'status' => StatusEnum::ACTIVE, 'published_at' => now(),
        ]);
        $this->get(route('admin.blogs.edit', $blog))->assertOk()
            ->assertSee('editor.getContent(), false);', false);
        $editor = Livewire::test(BlogForm::class, ['blogId' => $blog->id]);
        $updated = $content.'<p>Edited</p>';
        $payload = ['components' => [[
            'snapshot' => json_encode($editor->snapshot),
            'updates' => ['content' => $updated], 'calls' => [],
        ]]];
        $this->assertGreaterThan(12 * 1024 * 1024, strlen(json_encode($payload)));
        $response = $this->postJson(Livewire::getUpdateUri(), $payload, ['X-Livewire' => 'true'])->assertOk();
        $snapshot = json_decode($response->json('components.0.snapshot'), true);
        $this->assertSame($updated, $snapshot['data']['content']);
        $this->assertSame(htmlentities($content), $blog->fresh()->content);
    }

    public function test_payload_guard_still_rejects_requests_above_the_finite_limit(): void
    {
        $this->assertSame(16 * 1024 * 1024, config('livewire.payload.max_size'));
        $this->assertSame(10, config('livewire.payload.max_nesting_depth'));
        $this->assertSame(50, config('livewire.payload.max_calls'));
        $this->assertSame(200, config('livewire.payload.max_components'));
        $this->withoutExceptionHandling();
        $this->expectException(PayloadTooLargeException::class);
        $this->postJson(Livewire::getUpdateUri(), ['padding' => str_repeat('A', config('livewire.payload.max_size') + 1)], ['X-Livewire' => 'true']);
    }

    public function test_editors_escape_textarea_content_and_initialize_each_field_once(): void
    {
        app()->setLocale('en');
        $content = '</textarea><script>unexpected()</script><p>Text & image</p>';
        $html = Blade::render('<x-tinymce.editor name="content.nl" :value="$content" :livewire="false" height="240" />
            <x-tinymce.editor name="content.en" :value="$content" :livewire="false" height="480" /> @stack("script")', ['content' => $content]);
        $this->assertStringContainsString(e($content), $html);
        $this->assertStringNotContainsString($content, $html);
        $this->assertSame(1, substr_count($html, '/tinymce.min.js'));
        $this->assertSame(2, substr_count($html, 'if (!textarea || tinymce.get(textarea.id)) return;'));
        $this->assertStringNotContainsString("selector: 'textarea.tinymce'", $html);
        $this->assertStringNotContainsString('7.5.1', $html);
        $this->assertStringContainsString("editor.on('init change input undo redo'", $html);
    }
}
