<?php

namespace Tests\Feature;

use App\Actions\Settings\SettingSaveAction;
use App\Enums\StatusEnum;
use App\Helpers\AiSolutionRequest;
use App\Livewire\Frontend\Ai\SolutionAi;
use App\Models\AnswerAI;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Log;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AiSolutionApiTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        foreach (['ai:solution', 'settings:update', 'dashboard:access'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        settings()->set('gptApiKey', 'fake-ai-test-key');
        settings()->set('gptModel', 'gpt-6-luna');
        settings()->set('gptEffect', 'low');
        settings()->set('gptDeveloperMessage', AiSolutionRequest::developerPrompt());
        settings()->set('gptUserMessage', AiSolutionRequest::userPrompt());
        settings()->save();
        $this->actor = User::factory()->create(['gpt_limit' => 2]);
        $this->actor->givePermissionTo(['ai:solution', 'settings:update', 'dashboard:access']);
        $this->actingAs($this->actor);
        $lesson = Lesson::factory()->create();
        $topic = Topic::factory()->create(['lesson_id' => $lesson->id]);
        $this->question = Question::factory()->create([
            'lesson_id' => $lesson->id, 'topic_id' => $topic->id, 'locale' => 'nl',
            'attachment' => 'icons/fav-192.jpg', 'solution' => 'icons/fav-512.jpg',
        ]);
    }

    private function response(): array
    {
        return [
            'id' => 'resp_mock', 'status' => 'completed', 'model' => 'gpt-6-luna',
            'usage' => ['input_tokens' => 123, 'output_tokens' => 456],
            'output' => [['type' => 'message', 'status' => 'completed', 'content' => [['type' => 'output_text', 'text' => json_encode([
                'status' => 'ready',
                'steps' => [['rule' => 'Breuk', 'definition' => '', 'explanation' => 'Deel de teller door de noemer.', 'latex' => '\\frac{1}{2}']],
                'final_answer' => 'Het antwoord is een half.', 'final_answer_latex' => '\\frac{1}{2}',
            ])]]]],
        ];
    }

    private function aiComponent()
    {
        return Livewire::withoutLazyLoading()->test(SolutionAi::class, ['question' => $this->question, 'user' => $this->actor]);
    }

    public function test_valid_answer_is_saved_once_and_another_student_reuses_it_without_http(): void
    {
        Http::fake(['api.openai.com/*' => Http::response($this->response())]);
        $this->aiComponent()->call('askGpt')->assertSet('remainingGptLimit', 1)->assertSee('Het antwoord is een half.');
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['model'] === 'gpt-6-luna' && $body['text']['format']['strict']
                && str_contains($body['input'][1]['content'][0]['text'], 'Vraag-ID: '.$this->question->id)
                && str_starts_with($body['input'][1]['content'][2]['image_url'], 'data:image/jpeg;base64,')
                && ! isset($body['tools']);
        });
        $answer = AnswerAI::where('question_id', $this->question->id)->sole();
        $this->assertSame('\\frac{1}{2}', $answer->content['final_answer_latex']);
        $log = Log::where('table_name', 'chatgpt')->where('data_id', $this->question->id)->firstOrFail();
        $audit = json_decode($log->data, true);
        $this->assertSame($this->response(), $audit['output']);
        $this->assertSame(AiSolutionRequest::VERSION, $audit['body']['prompt_version']);
        $this->assertStringNotContainsString('base64', $log->data);
        $this->assertStringNotContainsString('fake-ai-test-key', $log->data);
        $this->actor = User::factory()->create(['gpt_limit' => 2]);
        $this->actor->givePermissionTo('ai:solution');
        $this->actingAs($this->actor);
        $this->aiComponent()->call('askGpt')->assertSet('remainingGptLimit', 1);
        Http::assertSentCount(1);
        $this->assertSame(1, AnswerAI::where('question_id', $this->question->id)->count());
        Bus::assertNothingDispatched();
    }

    public function test_invalid_json_does_not_save_an_answer_or_consume_quota_or_retry(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => '{"steps":[]}']]]]])]);
        $this->aiComponent()->call('askGpt')->assertSet('disabledAskGpt', true)->assertSet('remainingGptLimit', 2);
        Http::assertSentCount(1);
        $this->assertFalse(AnswerAI::where('question_id', $this->question->id)->exists());
        $this->assertSame(0, $this->actor->aiUsages()->count());
        $audit = json_decode(Log::where('table_name', 'chatgpt')->where('data_id', $this->question->id)->value('data'), true);
        $this->assertSame('invalid_solution_schema', $audit['error']);
    }

    public function test_rate_limit_does_not_trigger_a_retry_or_model_fallback(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['code' => 'rate_limit_exceeded']], 429)]);
        $this->aiComponent()->call('askGpt')->assertSet('disabledAskGpt', true);
        Http::assertSentCount(1);
        $this->assertSame(0, $this->actor->aiUsages()->count());
    }

    public function test_existing_question_lock_blocks_duplicate_generation_without_consuming_quota(): void
    {
        $lock = Cache::lock('ai-solution:'.$this->question->id, 150);
        $this->assertTrue($lock->get());
        try {
            $this->aiComponent()->call('askGpt')->assertSet('disabledAskGpt', true)
                ->assertSet('warningMessage', __('Çözüm yapılıyor, lütfen bekleyiniz..'));
            Http::assertNothingSent();
            $this->assertSame(0, $this->actor->aiUsages()->count());
        } finally {
            $lock->release();
        }
    }

    public function test_source_diagnostic_is_displayed_without_retry_saving_or_quota_consumption(): void
    {
        $response = $this->response();
        $response['output'][0]['content'][0]['text'] = json_encode([
            'status' => 'inconsistent', 'steps' => [],
            'final_answer' => 'Alle antwoordopties zijn delers; er is geen juiste keuze.',
            'final_answer_latex' => null,
        ]);
        Http::fake(['api.openai.com/*' => Http::response($response)]);
        $this->aiComponent()->call('askGpt')->assertSee('Alle antwoordopties zijn delers; er is geen juiste keuze.')
            ->assertDontSee('Yapay zeka ile çözüm detaylandıralamadı')
            ->assertSet('remainingGptLimit', 2)->assertSet('answerAi', null);
        Http::assertSentCount(1);
        $this->assertFalse(AnswerAI::where('question_id', $this->question->id)->exists());
        $this->assertSame(0, $this->actor->aiUsages()->count());
    }

    public function test_valid_prose_with_math_is_saved_and_a_stale_warning_is_cleared_on_reuse(): void
    {
        $response = $this->response();
        $content = json_decode($response['output'][0]['content'][0]['text'], true);
        $content['steps'][0]['definition'] = 'a^{m+n}=a^m\\cdot a^n.';
        $response['output'][0]['content'][0]['text'] = json_encode($content);
        Http::fake(['api.openai.com/*' => Http::response($response)]);
        $component = $this->aiComponent()->call('askGpt')->assertSee('a^{m+n}=a^m\\cdot a^n.');
        $component->set('warningMessage', 'stale failure')->set('disabledAskGpt', true)
            ->call('askGpt')->assertSet('warningMessage', '')->assertSet('disabledAskGpt', false)
            ->assertSee('Het antwoord is een half.')->assertDontSee('stale failure');
        Http::assertSentCount(1);
        $this->assertSame(1, AnswerAI::where('question_id', $this->question->id)->count());
        $this->assertSame(2, $this->actor->aiUsages()->count());
    }

    public function test_model_options_render_and_incompatible_effort_is_rejected_without_settings_write(): void
    {
        $this->assertStringContainsString('value="gpt-6-luna"', view('backend.settings.tabs.ai')->render());
        $this->assertStringContainsString('value="gpt-6-sol"', view('backend.settings.tabs.ai')->render());
        $request = Request::create('/', 'POST', ['settings' => ['gptModel' => 'gpt-6-sol', 'gptEffect' => 'minimal']]);
        $request->setUserResolver(fn () => $this->actor);
        try {
            SettingSaveAction::run($request);
            $this->fail('Unsupported model/effort was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('settings.gptEffect', $exception->errors());
        }
        $this->assertSame('gpt-6-luna', settings()->gptModel);
        Http::assertNothingSent();
    }

    public function test_malformed_model_input_is_a_validation_error_before_writes(): void
    {
        $request = Request::create('/', 'POST', ['settings' => ['gptModel' => ['gpt-6-luna'], 'gptEffect' => 'low']]);
        $request->setUserResolver(fn () => $this->actor);
        try {
            SettingSaveAction::run($request);
            $this->fail('Array model was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('settings.gptModel', $exception->errors());
        }
        Http::assertNothingSent();
    }

    public function test_legacy_plain_json_uses_local_formulas_without_rewriting_or_generating(): void
    {
        $content = ['steps' => [['definition' => 'Breuk', 'explanation' => 'De breuk is vereenvoudigd.', 'latex' => '$$\\frac{1}{2}$$']], 'final_answer' => 'Het antwoord is een half.'];
        $answer = AnswerAI::create(['question_id' => $this->question->id, 'user_id' => $this->actor->id, 'lesson_id' => $this->question->lesson_id, 'topic_id' => $this->question->topic_id, 'locale' => 'nl', 'content' => $content]);
        $this->aiComponent()->call('askGpt')->assertSee('Het antwoord is een half.')
            ->assertSee('data-ai-latex="\\frac{1}{2}"', false)->assertDontSee('latex.codecogs.com', false);
        $this->assertSame($content, $answer->fresh()->content);
        Http::assertNothingSent();
        Bus::assertNothingDispatched();
    }
}
