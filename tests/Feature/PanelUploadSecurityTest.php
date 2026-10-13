<?php

namespace Tests\Feature;

use App\Actions\Files\UploadFileAction;
use App\Actions\Settings\SettingSaveAction;
use App\Livewire\Blogs\BlogForm;
use App\Livewire\Questions\QuestionForm;
use App\Livewire\Tests\SelectPdfModalForm;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class PanelUploadSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $user = User::factory()->create();
        Permission::findOrCreate('dashboard:access', 'web');
        $user->givePermissionTo('dashboard:access');
        $this->actingAs($user);
    }

    public function test_genuine_image_keeps_content_matching_extension(): void
    {
        $file = UploadedFile::fake()->image('photo.png');
        $path = UploadFileAction::run($file, 'site');
        $this->assertStringEndsWith('.png', $path);
        Storage::disk('public')->assertExists(substr($path, strlen('uploads/')));
    }

    public function test_watermark_preserves_the_saved_image_format(): void
    {
        $file = UploadedFile::fake()->image('image.png', 100, 100);
        Storage::disk('public')->put('image.png', file_get_contents($file->getRealPath()));
        Storage::disk('public')->put('watermark.png', file_get_contents($file->getRealPath()));
        $previousPublicPath = public_path();
        $this->app->usePublicPath(Storage::disk('public')->path(''));
        try {
            settings()->set(['solutionWatermark' => 'active', 'solutionWatermarkLogo' => 'watermark.png']);
            settings()->save();
            $this->assertSame('image.png', applyWatermark($file, 'image.png'));
            $this->assertSame('image/png', mime_content_type(public_path('image.png')));
        } finally {
            $this->app->usePublicPath($previousPublicPath);
        }
    }

    public function test_executable_extension_svg_and_oversized_images_are_rejected(): void
    {
        $files = [
            UploadedFile::fake()->image('photo.php'),
            UploadedFile::fake()->createWithContent('photo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->image('big.png')->size(10241),
        ];
        foreach ($files as $file) {
            try {
                UploadFileAction::run($file, 'site');
                $this->fail('Güvensiz dosya reddedilmeliydi.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('file', $exception->errors());
            }
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_pdf_upload_accepts_pdf_and_rejects_image_in_pdf_folder(): void
    {
        $path = UploadFileAction::run(UploadedFile::fake()->createWithContent('exam.pdf', "%PDF-1.4\n%%EOF"), 'tests');
        $this->assertStringEndsWith('.pdf', $path);
        $this->expectException(ValidationException::class);
        UploadFileAction::run(UploadedFile::fake()->image('photo.png'), 'tests');
    }

    public function test_guest_cannot_write_public_uploads(): void
    {
        auth()->logout();
        try {
            UploadFileAction::run(UploadedFile::fake()->image('photo.png'), 'site');
            $this->fail('Misafir yükleme yapamamalı.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_panel_access_alone_does_not_allow_form_writes(): void
    {
        Livewire::test(BlogForm::class)->call('save')->assertForbidden();
        Livewire::test(QuestionForm::class)->call('save')->assertForbidden();
        Livewire::test(SelectPdfModalForm::class, ['sectionIndex' => 0, 'fieldIndex' => 0])
            ->call('save')->assertForbidden();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_settings_reject_general_files_before_writing(): void
    {
        Permission::findOrCreate('settings:update', 'web');
        auth()->user()->givePermissionTo('settings:update');
        $request = Request::create('/', 'POST', [], [], ['files' => ['document' => UploadedFile::fake()->create('file.php')]]);
        $request->setUserResolver(fn () => auth()->user());
        $this->expectException(ValidationException::class);
        SettingSaveAction::run($request);
    }
}
