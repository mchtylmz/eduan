<?php

namespace Tests\Unit;

use App\Helpers\AiSolutionRequest;
use App\Helpers\AiSolutionResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class AiSolutionResultTest extends TestCase
{
    private static function solution(): array
    {
        return [
            'status' => 'ready',
            'steps' => [['rule' => 'Breuken', 'definition' => '', 'explanation' => 'Deel de teller door de noemer.', 'latex' => '\\frac{1}{2}+\\beta']],
            'final_answer' => 'De breuk is vereenvoudigd.',
            'final_answer_latex' => '\\frac{1}{2}',
        ];
    }

    public static function response(array $solution): array
    {
        return ['status' => 'completed', 'output' => [['type' => 'message', 'status' => 'completed', 'content' => [['type' => 'output_text', 'text' => json_encode($solution, JSON_THROW_ON_ERROR)]]]]];
    }

    public function test_single_decode_preserves_formula_backslashes_and_separates_final_text(): void
    {
        $solution = self::solution();
        $this->assertSame($solution, AiSolutionResult::parse(self::response($solution)));
        $this->assertSame('\\begin{aligned}x&=1\\\\y&=2\\end{aligned}', AiSolutionResult::formula('\\begin{aligned}x&=1\\\\y&=2\\end{aligned}'));
    }

    public function test_harmless_math_in_escaped_prose_does_not_discard_a_valid_answer(): void
    {
        $solution = self::solution();
        // Observed failure: a completed answer used a multiplication command in its definition.
        $solution['steps'][0]['definition'] = 'a^{m+n}=a^m\\cdot a^n.';
        $this->assertSame($solution, AiSolutionResult::parse(self::response($solution)));
    }

    public function test_source_diagnostics_are_validated_without_becoming_ready_answers(): void
    {
        foreach (['inconsistent', 'unreadable'] as $status) {
            $diagnostic = ['status' => $status, 'steps' => [], 'final_answer' => 'De gegeven oplossing past niet bij de vraag.', 'final_answer_latex' => null];
            $this->assertSame($diagnostic, AiSolutionResult::parse(self::response($diagnostic)));
        }
    }

    #[DataProvider('invalidResponses')]
    public function test_invalid_or_unfinished_responses_are_not_answers(array $response): void
    {
        $this->expectException(\Throwable::class);
        AiSolutionResult::parse($response);
    }

    public static function invalidResponses(): array
    {
        $empty = self::solution();
        $empty['steps'] = [];
        $wrong = self::solution();
        $wrong['steps'][0]['latex'] = "\x0Crac{1}{2}";
        $extra = self::solution();
        $extra['extra'] = 'unexpected';
        $missing = self::solution();
        unset($missing['final_answer']);
        $unreadable = self::solution();
        $unreadable['status'] = 'unreadable';
        $html = self::solution();
        $html['steps'][0]['explanation'] = '<script>alert(1)</script>';

        return [
            'incomplete' => [['status' => 'incomplete']],
            'refusal' => [['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'refusal', 'refusal' => 'No']]]]]],
            'invalid json' => [['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => '{']]]]]],
            'empty steps' => [self::response($empty)],
            'corrupt backslash' => [self::response($wrong)],
            'extra key' => [self::response($extra)],
            'missing key' => [self::response($missing)],
            'unreadable' => [self::response($unreadable)],
            'html' => [self::response($html)],
        ];
    }

    #[DataProvider('invalidFormulas')]
    public function test_invalid_formula_structure_is_rejected(string $formula): void
    {
        $this->expectException(UnexpectedValueException::class);
        AiSolutionResult::formula($formula);
    }

    public static function invalidFormulas(): array
    {
        return [['\\frac{1}{2'], ['x}'], ['\\begin{aligned}x=1\\end{matrix}'], ['\\begin{aligned}x=1'], ['\\href{https://example.com}{x}'], ['$$x$$']];
    }

    public function test_both_requested_models_use_one_strict_schema_without_paid_tools(): void
    {
        foreach (['gpt-6-luna', 'gpt-6-sol'] as $model) {
            $body = AiSolutionRequest::build($model, 'low', '', 'ID {{problem_id}} {{term_1}}', ['first', 'second']);
            $this->assertSame($model, $body['model']);
            $this->assertTrue($body['text']['format']['strict']);
            $this->assertSame('json_schema', $body['text']['format']['type']);
            $this->assertArrayNotHasKey('tools', $body);
            $this->assertFalse($body['store']);
            $this->assertStringNotContainsString('{{', $body['input'][1]['content'][0]['text']);
            $this->assertSame(['VRAAG', 'OFFICIËLE OPLOSSING'], [$body['input'][1]['content'][1]['text'], $body['input'][1]['content'][3]['text']]);
        }
        $this->assertArrayNotHasKey('reasoning', AiSolutionRequest::build('gpt-4.1', 'low', '', '', []));
    }

    public function test_unsupported_effort_is_rejected_before_request(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AiSolutionRequest::build('gpt-6-luna', 'minimal', '', '', []);
    }
}
