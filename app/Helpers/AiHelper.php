<?php

namespace App\Helpers;

use App\Models\Log;
use App\Models\Question;
use Illuminate\Support\Facades\Http;
use Throwable;

class AiHelper
{
    private static string $apiUrl = 'https://api.openai.com/v1/responses';

    private array $images = [];

    private ?Question $question = null;

    public function gptLists(): array
    {
        return [
            'gpt-6-luna' => 'GPT-6 Luna',
            'gpt-6-sol' => 'GPT-6 Sol',
            'gpt-5' => __('Chat GPT 5'),
            'gpt-5-nano' => __('Chat GPT 5 Nano'),
            'gpt-5-mini' => __('Chat GPT 5 Mini'),
            'gpt-4.1' => __('Chat GPT 4.1'),
            'gpt-4.1-nano' => __('Chat GPT 4.1 Nano'),
            'gpt-4.1-mini' => __('Chat GPT 4.1 Mini'),
        ];
    }

    public function gptEffects(): array
    {
        return [
            'minimal' => __('Minimal'),
            'low' => __('Düşük'),
            'medium' => __('Orta'),
            'high' => __('Yüksek'),
        ];
    }

    public function getApiKey(): ?string
    {
        return settings()->gptApiKey ?: null;
    }

    public function forQuestion(Question $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function setImage(string $image): self
    {
        $mime = is_file($image) && is_readable($image) ? mime_content_type($image) : false;
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            throw new \InvalidArgumentException('invalid_input_image');
        }
        $contents = file_get_contents($image);
        if ($contents === false || $contents === '') {
            throw new \InvalidArgumentException('unreadable_input_image');
        }
        $this->images[] = 'data:'.$mime.';base64,'.base64_encode($contents);

        return $this;
    }

    public function getBody(): array
    {
        return AiSolutionRequest::build(
            settings()->gptModel ?: 'gpt-6-luna',
            settings()->gptEffect ?: 'low',
            settings()->gptDeveloperMessage ?: '',
            settings()->gptUserMessage ?: '',
            $this->images,
            $this->question,
        );
    }

    public function output(): array|false
    {
        $started = microtime(true);
        $body = [];
        $raw = [];
        $httpStatus = null;
        $error = null;
        $result = false;
        try {
            if (! $this->getApiKey()) {
                throw new \RuntimeException('missing_api_key');
            }
            if (count($this->images) !== 2) {
                throw new \RuntimeException('missing_question_or_solution_image');
            }
            $body = $this->getBody();
            // One request only: no automatic retry, image generation or model fallback.
            $response = Http::acceptJson()->connectTimeout(10)->timeout(120)
                ->withToken($this->getApiKey())->post(self::$apiUrl, $body);
            $httpStatus = $response->status();
            $raw = $response->json() ?: [];
            if (! $response->successful()) {
                throw new \RuntimeException('http_error');
            }
            $result = AiSolutionResult::parse($raw);
        } catch (Throwable $exception) {
            // Do not log exception messages from the transport: these may contain credentials.
            $error = in_array($exception::class, [\RuntimeException::class, \InvalidArgumentException::class, \UnexpectedValueException::class], true)
                ? $exception->getMessage() : class_basename($exception);
        }
        // Retain the exact response/usage for inspection, never the key or base64 images.
        try {
            Log::insert([
                'user_id' => auth()->id(),
                'log_date' => now(),
                'table_name' => 'chatgpt',
                'log_type' => $result === false ? 'error' : 'create',
                'ip' => request()->ip(),
                'data_id' => $this->question?->id ?? 0,
                'data' => json_encode([
                    'result' => $result,
                    'output' => $raw,
                    'body' => [
                        'model' => $body['model'] ?? null,
                        'reasoning' => $body['reasoning'] ?? null,
                        'max_output_tokens' => $body['max_output_tokens'] ?? null,
                        'image_count' => count($this->images),
                        'prompt_version' => AiSolutionRequest::VERSION,
                        'prompt_hash' => hash('sha256', json_encode($body['input'][0] ?? []).json_encode($body['input'][1]['content'][0] ?? [])),
                    ],
                    'http_status' => $httpStatus,
                    'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                    'error' => $error,
                ], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE),
                'agent' => request()->server('HTTP_USER_AGENT'),
                'browser' => request()->server('HTTP_SEC_CH_UA'),
                'device' => request()->server('HTTP_SEC_CH_UA_PLATFORM'),
            ]);
        } catch (Throwable) {
            // A log write failure must not discard a valid paid response and cause regeneration.
            \Illuminate\Support\Facades\Log::warning('AI audit log could not be written.', ['question_id' => $this->question?->id]);
        }

        return $result;
    }
}
