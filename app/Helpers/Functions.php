<?php

use App\Enums\StatusEnum;
use App\Helpers\AiHelper;
use App\Helpers\DataHelper;
use App\Jobs\LatexImageDownloadJob;
use App\Models\Exam;
use App\Models\LatexImage;
use App\Models\Season;
use App\Models\TestsSection;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

if (! function_exists('applyWatermark')) {
    /**
     * @return Authenticatable|null
     */
    function applyWatermark(UploadedFile $file, string $inputImagePath): bool|string
    {
        if (StatusEnum::ACTIVE->value != settings()->solutionWatermark) {
            return false;
        }

        $watermarkPath = public_path(settings()->solutionWatermarkLogo);
        $imagePath = public_path($inputImagePath);

        if (! file_exists($watermarkPath) || ! is_readable($watermarkPath)) {
            return false;
        }
        if (! file_exists($imagePath) || ! is_readable($imagePath)) {
            return false;
        }

        try {
            $image = ImageManager::imagick()->read($file->getRealPath());
            $watermark = ImageManager::imagick()->read($watermarkPath);
            $watermark->scale($image->width() - intval($image->width() / 3));
            $watermark->rotate(45);

            $image->place($watermark, position: 'center', opacity: 10);
            $image->encodeByExtension(pathinfo($imagePath, PATHINFO_EXTENSION))->save($imagePath);
        } catch (Exception $exception) {
            return false;
        }

        return $inputImagePath;
    }
}

if (! function_exists('agentDevice')) {
    /**
     * @return Factory|View|Application|object
     */
    function agentDevice(): string
    {
        $agent = request()->userAgent();

        if (stripos($agent, 'iPhone') !== false || stripos($agent, 'iPad') !== false) {
            return 'ios';
        } elseif (stripos($agent, 'Android') !== false) {
            return 'android';
        }

        return 'desktop';
    }

}

if (! function_exists('seasons')) {

    function seasons(): mixed
    {
        return Season::orderBy('id', 'desc')->get();
    }
}

if (! function_exists('activeSeason')) {

    function activeSeason(): mixed
    {
        $now = now()->timezone(settings()->timezone ?? config('app.timezone'))->format('Y-m-d');

        return Season::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->orderBy('id', 'DESC')
            ->first();
    }
}

if (! function_exists('isChrome')) {

    function isChrome(): bool
    {
        $userAgent = request()->header('User-Agent');

        return str_contains($userAgent, 'Chrome') && ! str_contains($userAgent, 'Edg');
    }
}

if (! function_exists('examResultStats')) {

    function examResultStats(Exam $exam, ?User $user = null): array
    {
        $totals = $exam->results()
            ->leftJoin('exam_result_details as details', 'details.exam_result_id', '=', 'exam_results.id')
            ->when($user, fn ($query) => $query->where('exam_results.user_id', $user->id))
            ->selectRaw('COUNT(DISTINCT exam_results.id) AS results_count, COUNT(details.id) AS question_count,
                COALESCE(SUM(details.correct = 1), 0) AS correct_count,
                COALESCE(SUM(details.correct = 0), 0) AS incorrect_count')
            ->toBase()->first();

        return [
            'results_count' => (int) $totals->results_count,
            'question_count' => (int) $totals->question_count,
            'correct_count' => (int) $totals->correct_count,
            'incorrect_count' => (int) $totals->incorrect_count,
        ];
    }
}

if (! function_exists('testsSectionGetContent')) {
    function testsSectionGetContent(int $testId, int $sectionId): ?TestsSection
    {
        return Cache::remember(
            'testsSectionGetContent_'.$testId.'_'.$sectionId,
            60 * 60 * 24 * 7,
            fn () => TestsSection::where('test_id', $testId)
                ->where('id', $sectionId)
                ->first()
        );
    }
}

if (! function_exists('user')) {
    /**
     * @param  null  $key
     */
    function user($key = null): ?Authenticatable
    {
        if ($key) {
            return auth()->user()?->{$key} ?? null;
        }

        return auth()?->user();
    }
}

if (! function_exists('singular')) {
    function singular(string $text): string
    {
        return str_replace(
            ['rleri', 'leri', 'ler', 'ları', 'lar'],
            ['ri', 'si', '', 'sı', ''],
            Str::singular($text)
        );
    }
}

if (! function_exists('letters')) {
    function letters(?int $index = null): string|array
    {
        $letters = range('A', 'Z');
        if (! is_null($index)) {
            return $letters[$index] ?? 'A';
        }

        return $letters;
    }
}

if (! function_exists('data')) {

    function data(): DataHelper
    {
        return new DataHelper;
    }
}

if (! function_exists('aiHelper')) {

    function aiHelper(): AiHelper
    {
        return new AiHelper;
    }
}

if (! function_exists('getJsonLocaleValue')) {
    function getJsonLocaleValue($value): string
    {
        return json_decode($value, true)[app()->getLocale()] ?? '';
    }
}

if (! function_exists('getImage')) {
    function getImage(?string $image, ?string $default = null): string
    {
        if (! is_file(public_path($image))) {
            return asset($default ?: settings()->siteLogo);
        }

        if (file_exists(public_path($image))) {
            return url($image);
        }

        return asset($image);
    }
}

if (! function_exists('dateFormat')) {

    function dateFormat(?string $value, string $format = 'd/m/Y, l'): string
    {
        if (! is_string($value)) {
            return '';
        }

        return Carbon::parse($value)->timezone(settings()->timezone ?? config('app.timezone'))->translatedFormat($format);
    }
}

if (! function_exists('timeAgo')) {
    function timeAgo(?string $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return Carbon::parse($value)->timezone(settings()->timezone ?? config('app.timezone'))->diffForHumans();
    }
}

if (! function_exists('formatSecondToTime')) {

    /**
     * @param  string  $time  // 01:40:00 or 00:45:44
     */
    function formatSecondToTime(string $time, bool $hideSeconds = false): string
    {
        $parts = explode(':', $time);

        $hours = (int) ($parts[0] ?? 0);
        $minutes = (int) ($parts[1] ?? 0);
        $seconds = (int) ($parts[2] ?? 0);

        $interval = CarbonInterval::hours($hours)->minutes($minutes)->seconds($seconds);

        $parts = [];

        if ($interval->hours > 0) {
            $parts[] = $interval->hours.' '.__('saat');
        }

        if ($interval->minutes > 0) {
            $parts[] = $interval->minutes.' '.__('dakika');
        }

        if ($interval->seconds > 0) {
            $parts[] = $interval->seconds.' '.__('saniye');

            if ($hideSeconds && count($parts) >= 2) {
                unset($parts[count($parts) - 1]);
            }
        }

        return implode(' ', $parts);
    }
}

if (! function_exists('settingLocale')) {
    function settingLocale(string $key, ?string $locale = null): ?string
    {
        return data()->setting($key, $locale ?? app()->getLocale());
    }
}

if (! function_exists('routeIs')) {
    function routeIs(...$patterns): bool
    {
        return request()->routeIs($patterns);
    }
}

if (! function_exists('incrementIf')) {

    function incrementIf(bool $condition, ?Model $model = null, string $column = 'hits'): void
    {
        if ($condition) {
            if ($column === 'hits') {
                $model::withoutTimestamps(fn () => $model->increment($column));
            } else {
                $model->increment($column);
            }
        }
    }
}

if (! function_exists('flush')) {
    function flush(): void
    {
        cache()->flush();
        cache()->clear();
    }
}

if (! function_exists('resetCache')) {
    function resetCache(): void
    {
        // Invalidate runtime data; compiled views/config/routes are deployment concerns.
        cache()->flush();
    }
}

if (! function_exists('convertLatexToImg')) {
    function convertLatexToImg(string $text, string $key = ''): string
    {
        if (in_array($key, ['latex', 'final_answer']) && ! str_contains($text, '$$')) {
            $text = sprintf('$$%s$$', $text);
        }

        if (! str_contains($text, '$$')) {
            return $text;
        }

        return preg_replace_callback('/\$\$(.*?)\$\$/s', function ($matches) {
            $formula = str_replace(
                [
                    '$$',
                    '<span>',
                    '<span class="latex-source">',
                    '</span>',
                    '<p>',
                    '</p>',
                    '<div>',
                    '</div>',
                ],
                '',
                trim($matches[1] ?? $matches[0])
            );

            // Daha önce kaydedilmişse DB’den al
            $existingLatex = LatexImage::where('formula', $formula)->first();
            if ($existingLatex) {
                return sprintf(
                    '<img class="latex-svg-image" src="%s" alt="%s">',
                    asset($existingLatex->image),
                    e($formula)
                );
            }

            // Kayıtlı değilse arkaplana indir talimati ver
            LatexImageDownloadJob::dispatch($formula);

            // latex den direk link ver
            return sprintf(
                '<img src="https://latex.codecogs.com/svg.image?%s" alt="%s">',
                urlencode($formula),
                e($formula)
            );
        }, $text);
    }
}

if (! function_exists('secondToTime')) {

    function secondToTime(?int $seconds = 0): string
    {
        $seconds = $seconds ?? 0;

        $hour = floor($seconds / 3600);
        $minute = floor(($seconds % 3600) / 60);
        $second = $seconds % 60;

        return str_pad($hour, 2, '0', STR_PAD_LEFT).':'.
            str_pad($minute, 2, '0', STR_PAD_LEFT).':'.
            str_pad($second, 2, '0', STR_PAD_LEFT);
    }
}

if (! function_exists('randomBackgroundClass')) {
    function randomBackgroundClass(): string
    {
        return Arr::random([
            'bg-alt-primary',
            'bg-alt-secondary',
            'bg-alt-success',
            'bg-alt-danger',
            'bg-alt-warning',
            'bg-alt-info',
            'bg-primary',
            'bg-secondary',
            'bg-success',
            'bg-danger',
            'bg-warning',
            'bg-info',
            'bg-dark',
            'bg-body-light',
        ]);
    }
}

if (! function_exists('replaceEmailVariables')) {

    function replaceEmailVariables(string $content, array $values): string
    {
        $fields = [
            '[ad]' => 'name',
            '[soyad]' => 'surname',
            '[ad_soyad]' => 'display_name',
            '[email]' => 'email',
            '[phone]' => 'phone',
            '[telefon]' => 'phone',
            '[okul_adi]' => 'school_name',
            '[kayit_tarihi]' => 'created_at',
        ];
        $fields = collect($fields)->mapWithKeys(function ($fieldValue, $fieldKey) use ($values) {
            if ($fieldValue == 'display_name' && ! data_get($values, $fieldValue)) {
                return [$fieldKey => e(sprintf(
                    '%s %s',
                    data_get($values, 'name') ?? '', data_get($values, 'surname') ?? ''
                ), true)];
            }

            return [$fieldKey => e((string) (data_get($values, $fieldValue) ?? ''), true)];
        })->toArray();

        // Decode only the authored template, never the interpolated user values.
        $content = str_replace(['<pre>', '</pre>'], '', html_entity_decode($content));

        return strtr($content, $fields);
    }
}
