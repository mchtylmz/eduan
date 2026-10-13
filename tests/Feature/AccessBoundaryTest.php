<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Enums\VisibilityEnum;
use App\Livewire\Exams\ExamForm;
use App\Livewire\Languages\LanguageTranslateForm;
use App\Livewire\Tests\TestForm;
use App\Models\Exam;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Test;
use App\Models\Topic;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccessBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        if (($_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE')) !== 'testing') {
            throw new \RuntimeException('AccessBoundaryTest yalnız testing veritabanında çalışır.');
        }

        parent::setUp();

        $this->assertSame('testing', DB::connection()->getDatabaseName());
    }

    public function test_guest_exam_start_redirects_to_login(): void
    {
        $test = Test::create([
            'locale' => 'nl',
            'code' => 'guest-start',
            'name' => 'Guest start',
            'status' => StatusEnum::ACTIVE,
            'created_by' => 1,
        ]);

        $this->get(route('frontend.exam.start', $test->code))
            ->assertRedirect(route('login'));
    }

    public function test_exam_form_save_requires_add_permission(): void
    {
        $initialExamCount = Exam::count();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ExamForm::class)
            ->call('save')
            ->assertForbidden();

        $this->assertSame($initialExamCount, Exam::count());
    }

    public function test_test_form_update_requires_tests_update_permission(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('exams:update', 'web');
        Permission::findOrCreate('tests:update', 'web');
        $user->givePermissionTo('exams:update');
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands']);
        $test = Test::create([
            'locale' => 'nl',
            'code' => 'protected-test',
            'name' => 'Original',
            'content' => '',
            'duration' => 300,
            'passing_score' => 60,
            'correct_point' => 3,
            'incorrect_point' => -1,
            'status' => StatusEnum::ACTIVE,
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TestForm::class, ['test' => $test])
            ->assertSet('permission', 'tests:update')
            ->set('name', 'Changed')
            ->call('save')
            ->assertForbidden();

        $this->assertSame('Original', $test->fresh()->name);

        $user->givePermissionTo('tests:update');

        Livewire::actingAs($user)
            ->test(TestForm::class, ['test' => $test])
            ->set('name', 'Changed')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Changed', $test->fresh()->name);
    }

    public function test_exam_add_permission_allows_creating_an_exam_with_questions(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('exams:add', 'web');
        $user->givePermissionTo('exams:add');
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands']);
        $lesson = Lesson::create(['name' => 'Lesson']);
        $topic = Topic::create(['lesson_id' => $lesson->id, 'title' => 'Topic']);
        $question = Question::create([
            'locale' => 'nl',
            'lesson_id' => $lesson->id,
            'topic_id' => $topic->id,
            'title' => 'Question',
            'attachment' => 'uploads/logo.png',
            'solution' => 'uploads/logo.png',
        ]);

        Livewire::actingAs($user)
            ->test(ExamForm::class)
            ->set('locale', 'nl')
            ->set('code', 'new-exam')
            ->set('name', 'New exam')
            ->call('toggleQuestions', $question->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.exams.index'));

        $exam = Exam::where('code', 'new-exam')->firstOrFail();
        $this->assertSame($question->id, $exam->questions()->first()->id);
    }

    public function test_exam_question_relations_cannot_be_changed_by_direct_livewire_call(): void
    {
        $user = User::factory()->create();
        $exam = Exam::create([
            'locale' => 'nl',
            'code' => 'protected-exam',
            'name' => 'Protected exam',
            'content' => '',
            'visibility' => VisibilityEnum::PREMIUM,
            'status' => StatusEnum::ACTIVE,
            'created_by' => $user->id,
        ]);

        $this->expectException(MethodNotFoundException::class);

        Livewire::actingAs($user)
            ->test(ExamForm::class, ['exam' => $exam])
            ->call('updateExamRelations', $exam->id);
    }

    public function test_translation_page_requires_translate_permission_before_writing(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('languages:view', 'web');
        $user->givePermissionTo('languages:view');
        $language = Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands']);
        $initialTranslationCount = Translation::count();
        $translation = Translation::create(['locale' => 'nl', 'key' => 'Security check', 'value' => 'Original']);

        Livewire::withoutLazyLoading();

        Livewire::actingAs($user)
            ->test(LanguageTranslateForm::class, ['languageId' => $language->id])
            ->assertForbidden();

        $this->assertSame('Original', $translation->fresh()->value);
        $this->assertSame($initialTranslationCount + 1, Translation::count());
    }

    public function test_translator_can_edit_only_the_selected_language_and_build_its_json(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('languages:translate', 'web');
        Permission::findOrCreate('dashboard:access', 'web');
        $user->givePermissionTo(['languages:translate', 'dashboard:access']);
        $language = Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands']);
        $own = Translation::create(['locale' => 'nl', 'key' => 'Welcome', 'value' => 'Welkom']);
        $other = Translation::create(['locale' => 'en', 'key' => 'Welcome', 'value' => 'Welcome']);

        Livewire::withoutLazyLoading();
        $form = Livewire::actingAs($user)
            ->test(LanguageTranslateForm::class, ['languageId' => $language->id])
            ->set('translations.'.$own->id.'.value', 'Nieuw');
        $form->set('translations.'.$other->id.'.value', 'Changed')->assertNotFound();

        $this->assertSame('Nieuw', $own->fresh()->value);
        $this->assertSame('Welcome', $other->fresh()->value);

        $this->partialMock(Filesystem::class, function ($mock) {
            // Grouped translations are published too; no test may write project files.
            $mock->shouldReceive('replace')
                ->with(\Mockery::on(fn ($path) => str_starts_with($path, lang_path('nl').DIRECTORY_SEPARATOR) && str_ends_with($path, '.php')), \Mockery::type('string'))
                ->andReturnNull();
            $mock->shouldReceive('replace')
                ->once()
                ->with(lang_path('nl').'.json', \Mockery::type('string'));
        });

        config(['cache.stores.frontend.driver' => 'array']);
        Livewire::actingAs($user)->test(LanguageTranslateForm::class, ['languageId' => $language->id])
            ->call('save')->assertRedirect(route('admin.languages.index'));
    }

    public function test_translation_write_is_denied_after_permission_is_revoked(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('languages:translate', 'web');
        Permission::findOrCreate('dashboard:access', 'web');
        $user->givePermissionTo(['languages:translate', 'dashboard:access']);
        $language = Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands']);
        $translation = Translation::create(['locale' => 'nl', 'key' => 'Welcome', 'value' => 'Welkom']);

        Livewire::withoutLazyLoading();
        $form = Livewire::actingAs($user)
            ->test(LanguageTranslateForm::class, ['languageId' => $language->id]);

        $user->revokePermissionTo('languages:translate');

        $form->set('translations.'.$translation->id.'.value', 'Changed')->assertForbidden();
        $this->assertSame('Welkom', $translation->fresh()->value);
    }
}
