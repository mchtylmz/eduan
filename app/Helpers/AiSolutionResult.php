<?php

namespace App\Helpers;

use UnexpectedValueException;

class AiSolutionResult
{
    public static function parse(array $response): array
    {
        if (($response['status'] ?? null) !== 'completed') {
            throw new UnexpectedValueException('response_not_completed');
        }
        $texts = [];
        foreach ($response['output'] ?? [] as $item) {
            if (($item['type'] ?? '') !== 'message') {
                continue;
            }
            if (($item['status'] ?? 'completed') !== 'completed') {
                throw new UnexpectedValueException('message_not_completed');
            }
            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? '') === 'refusal') {
                    throw new UnexpectedValueException('response_refused');
                }
                if (($content['type'] ?? '') === 'output_text') {
                    $texts[] = $content['text'] ?? '';
                }
            }
        }
        if (count($texts) !== 1 || ! is_string($texts[0]) || strlen($texts[0]) > 100000) {
            throw new UnexpectedValueException('invalid_output_text');
        }
        // Decode exactly once: a second unescape corrupts commands such as \frac and \beta.
        $data = json_decode($texts[0], true, 64, JSON_THROW_ON_ERROR);
        self::keys($data, ['status', 'steps', 'final_answer', 'final_answer_latex']);
        if (in_array($data['status'] ?? null, ['unreadable', 'inconsistent'], true)) {
            self::plainText($data['final_answer']);
            if ($data['steps'] !== [] || $data['final_answer_latex'] !== null || trim($data['final_answer']) === '') {
                throw new UnexpectedValueException('invalid_solution_diagnostic');
            }

            return $data;
        }
        if (($data['status'] ?? null) !== 'ready') {
            throw new UnexpectedValueException('invalid_solution_status');
        }
        if (! is_array($data['steps']) || ! array_is_list($data['steps']) || count($data['steps']) < 1 || count($data['steps']) > 30) {
            throw new UnexpectedValueException('invalid_steps');
        }
        foreach ($data['steps'] as &$step) {
            self::keys($step, ['rule', 'definition', 'explanation', 'latex']);
            foreach (['rule', 'definition', 'explanation'] as $key) {
                self::plainText($step[$key]);
            }
            if (trim($step['explanation']) === '') {
                throw new UnexpectedValueException('empty_explanation');
            }
            $step['latex'] = self::formula($step['latex']);
        }
        unset($step);
        self::plainText($data['final_answer']);
        $data['final_answer_latex'] = self::formula($data['final_answer_latex']);
        if (trim($data['final_answer']) === '' && ! $data['final_answer_latex']) {
            throw new UnexpectedValueException('empty_final_answer');
        }

        return $data;
    }

    private static function keys(mixed $data, array $keys): void
    {
        if (! is_array($data) || array_diff($keys, array_keys($data)) || array_diff(array_keys($data), $keys)) {
            throw new UnexpectedValueException('invalid_solution_schema');
        }
    }

    private static function plainText(mixed $text): void
    {
        // Prose is escaped by Blade, never evaluated as TeX or HTML. A harmless
        // mathematical command in prose must not discard an otherwise valid paid answer.
        if (! is_string($text) || strlen($text) > 6000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $text) || strip_tags($text) !== $text) {
            throw new UnexpectedValueException('invalid_solution_text');
        }
    }

    public static function formula(mixed $formula): ?string
    {
        if ($formula === null) {
            return null;
        }
        if (! is_string($formula)) {
            throw new UnexpectedValueException('invalid_latex_type');
        }
        $formula = trim($formula);
        if ($formula === '') {
            return null;
        }
        if (strlen($formula) > 6000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F$]/', $formula)
            || preg_match('/\\\\(?:input|include|includegraphics|href|url|html\w*|def|gdef|newcommand|renewcommand|usepackage|documentclass|write|csname)\b/i', $formula)) {
            throw new UnexpectedValueException('invalid_latex');
        }
        $depth = 0;
        for ($index = 0, $length = strlen($formula); $index < $length; $index++) {
            if ($formula[$index] === '\\') {
                $index++;

                continue;
            }
            if ($formula[$index] === '{') {
                $depth++;
            } elseif ($formula[$index] === '}' && --$depth < 0) {
                throw new UnexpectedValueException('unbalanced_latex');
            }
        }
        if ($depth !== 0) {
            throw new UnexpectedValueException('unbalanced_latex');
        }
        preg_match_all('/\\\\(begin|end)\{([^}]+)\}/', $formula, $environments, PREG_SET_ORDER);
        $stack = [];
        foreach ($environments as $environment) {
            if ($environment[1] === 'begin') {
                $stack[] = $environment[2];
            } elseif (array_pop($stack) !== $environment[2]) {
                throw new UnexpectedValueException('unbalanced_latex_environment');
            }
        }
        if ($stack) {
            throw new UnexpectedValueException('unbalanced_latex_environment');
        }

        return $formula;
    }
}
