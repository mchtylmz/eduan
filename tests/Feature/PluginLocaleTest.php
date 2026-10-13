<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PluginLocaleTest extends TestCase
{
    use DatabaseTransactions;

    public static function locales(): array
    {
        return [['nl'], ['en'], ['tr']];
    }

    #[DataProvider('locales')]
    public function test_panel_editor_and_local_script_urls_match_the_locale(string $locale): void
    {
        Mail::fake();
        Queue::fake();
        $user = User::factory()->create();
        foreach (['dashboard:access', 'blogs:view', 'blogs:add'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
        }

        $response = $this->actingAs($user)->withSession(['locale' => $locale])
            ->get(route('admin.blogs.create'))->assertOk();
        $html = $response->getContent();
        $this->assertLocalScriptsExist($html);
        $this->assertEditorLanguage($html, $locale);
        $this->assertStringContainsString('window.uploadTranslations = ', $html);
        $this->assertIsArray(__('upload'));
        $this->assertCount(4, __('upload.messages'));
        $this->assertCount(7, __('upload.error'));
        $this->assertStringNotContainsString('messages_en.min.js', $html);
        $this->assertStringNotContainsString('defaults-en_EN.min.js', $html);

        // Sınav bölüm modal'ı ayrı bir TinyMCE init kullanır.
        $this->assertEditorLanguage(view('backend.layouts.app', ['tinymce' => true])->render(), $locale);
        Mail::assertNothingQueued();
        Queue::assertNothingPushed();

        $blog = Blog::create([
            'locale' => 'nl', 'slug' => 'plugin-locale-test', 'title' => 'Locale test',
            'content' => '<p>Content</p>', 'status' => StatusEnum::ACTIVE, 'published_at' => now(),
        ]);
        Permission::findOrCreate('blogs:update', 'web');
        $user->givePermissionTo('blogs:update');
        $this->withSession(['locale' => $locale])->get(route('admin.blogs.edit', $blog))
            ->assertOk()->assertSee('data-locale="'.$locale.'"', false);
        $this->assertSame('nl', $blog->fresh()->locale);
    }

    #[DataProvider('locales')]
    public function test_frontend_only_requests_existing_locale_scripts(string $locale): void
    {
        $html = $this->withSession(['locale' => $locale])->get(route('login'))->assertOk()->getContent();
        $this->assertLocalScriptsExist($html);
        $this->assertStringNotContainsString('flatpickr/l10n/en.js', $html);
        if ($locale !== 'en') {
            $this->assertStringContainsString('flatpickr/l10n/'.$locale.'.js', $html);
        }
    }

    #[DataProvider('locales')]
    public function test_non_livewire_editor_uses_the_same_locale(string $locale): void
    {
        app()->setLocale($locale);
        $html = Blade::render('<x-tinymce.editor name="content" :livewire="false" /> @stack("script")');
        $this->assertEditorLanguage($html, $locale);
    }

    private function assertLocalScriptsExist(string $html): void
    {
        preg_match_all('/<script[^>]+src="([^"]+)"/', $html, $matches);
        foreach ($matches[1] as $url) {
            $path = parse_url($url, PHP_URL_PATH);
            if (str_starts_with($path, '/backend/assets/') || str_starts_with($path, '/assets/')) {
                $this->assertFileExists(public_path(ltrim($path, '/')), $url);
            }
        }
    }

    private function assertEditorLanguage(string $html, string $locale): void
    {
        $this->assertStringContainsString('keynav/'.$locale.'.min.js', $html);
        $this->assertDoesNotMatchRegularExpression('/<!--[^\n]*language_url:/', $html);
        if ($locale === 'en') {
            $this->assertStringContainsString("language_url: ''", $html);
            $this->assertStringContainsString("language: 'en'", $html);
            $this->assertStringNotContainsString('tinymce_tr.js', $html);

            return;
        }

        $this->assertStringContainsString('tinymce_'.$locale.'.js', $html);
        $this->assertStringContainsString("language: '$locale'", $html);
        $this->assertFileExists(public_path('backend/assets/langs/tinymce_'.$locale.'.js'));
    }
}
