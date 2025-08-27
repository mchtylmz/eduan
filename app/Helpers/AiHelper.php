<?php

namespace App\Helpers;

use App\Models\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiHelper
{
    private static string $apiUrl = 'https://api.openai.com/v1/responses';

    private array $images = [];

    public function gptLists(): array
    {
        return [
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

    public function getApiKey(): string|null
    {
        return settings()->gptApiKey ?: false;
    }

    public function setImage(string $image): AiHelper
    {
        $path = file_get_contents($image);
        $extension = pathinfo($image, PATHINFO_EXTENSION);

        $this->images[] = 'data:image/' . $extension . ';base64,' . base64_encode($path);

        return $this;
    }

    private function gptDeveloperMessage(): array
    {
        return [
            'role' => 'developer',
            'content' => [
                [
                    'type' => 'input_text',
                    'text' => sprintf(
                        '%s Alleen de sleutel- en variabelenamen in het JSON-object mogen in het Engels zijn. for json object: STAPPEN -> steps, answer_consistency_note -> final_answer. ',
                        settings()->gptDeveloperMessage ?: ''
                    )
                ]
            ]
        ];
    }

    private function gptUserMessage(): array
    {
        return [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'input_text',
                    'text' => settings()->gptUserMessage ?: ''
                ]
            ]
        ];
    }

    private function getInputImages(): array
    {
        $content = [];

        foreach ($this->images as $image) {
            $content[] = [
                'type' => 'input_image',
                'image_url' => $image
            ];
        }

        return $content;
    }

    public function getBody(): array
    {
        $input = [
            $this->gptDeveloperMessage(),
            $this->gptUserMessage()
        ];
        if ($images = $this->getInputImages()) {
            $input[] = [
                'role' => 'user',
                'content' => $images
            ];
        }

        return [
            'model' => array_key_exists(settings()->gptModel, $this->gptLists()) ? settings()->gptModel : 'gpt-5',
            'input' => $input,
            'text' => [
                'format' => [
                    'type' => 'json_object'
                ],
                'verbosity' => 'low'
            ],
            'reasoning' => [
                'effort' => array_key_exists(settings()->gptEffect, $this->gptEffects()) ? settings()->gptEffect : 'low',
                'summary' => 'auto'
            ],
            'tools' => [
                [
                    'type' => 'image_generation',
                    'background' => 'transparent',
                    'moderation' => 'auto',
                    'output_compression' => 100,
                    'output_format' => 'png',
                    'quality' => 'medium',
                    'size' => '1024x1536'
                ]
            ],
            'store' => true
        ];
    }

    public function output()
    {
        try {
            $response = Http::acceptJson()
                ->timeout(120)
                ->withoutVerifying()
                ->withOptions(["verify" => false])
                ->withToken($this->getApiKey())
                ->withBody(json_encode($getBody = $this->getBody()))
                ->post(self::$apiUrl)
                ->throw();

            $output = collect($response->json('output'))
                ->firstWhere('type', 'message');

            $content = collect($output['content'] ?? [])
                ->firstWhere('type', 'output_text');

            $outputText = json_decode(collect($content)->get('text', ''), true) ?? [];

            Log::insert([
                'user_id'    => auth()->id(),
                'log_date'   => now(),
                'table_name' => 'chatgpt',
                'log_type'   => 'create',
                'ip'         => request()->ip(),
                'data_id'    => 0,
                'data'       => json_encode([
                    'result' => $outputText,
                    'output' => $output,
                    'body' => $getBody,
                ]),
                'agent'      => request()->server('HTTP_USER_AGENT'),
                'browser'    => request()->server('HTTP_SEC_CH_UA'),
                'device'     => request()->server('HTTP_SEC_CH_UA_PLATFORM')
            ]);
        } catch (\Exception $exception) {
            return false;
        }

        return $outputText ?? '';
    }
}
