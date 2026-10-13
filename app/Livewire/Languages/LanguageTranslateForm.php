<?php

namespace App\Livewire\Languages;

use App\Helpers\TranslationHelper;
use App\Helpers\TranslationScanner;
use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class LanguageTranslateForm extends Component
{
    use WithPagination;

    #[Locked]
    public Language $language;

    public array $translations = [];

    public string $searchOriginal = '';

    public string $searchTranslate = '';

    public string $surface = '';

    public string $group = '';

    public string $sourceSearch = '';

    public string $completion = '';

    #[Locked]
    public array $scanSummary = [];

    #[Locked]
    public string $scanToken = '';

    public function mount(int $languageId): void
    {
        $this->authorizeTranslation();
        $this->language = Language::findOrFail($languageId);
    }

    private function authorizeTranslation(): void
    {
        abort_unless(auth()->user()?->can('dashboard:access') && auth()->user()?->can('languages:translate'), 403);
    }

    #[Computed]
    public function catalogReady(): bool
    {
        $this->authorizeTranslation();

        return Schema::hasTable('translation_sources');
    }

    #[Computed]
    public function previewSources()
    {
        $this->authorizeTranslation();
        if (! $this->catalogReady) {
            return null;
        }
        $preview = $this->scanToken !== '' ? Cache::get($this->scanCacheKey()) : null;
        if (! $preview && TranslationSource::where('active', true)->exists()) {
            return null;
        }
        // Filtering must also work before the administrator applies a scan.
        // Cache server-side metadata only; never insert or change translations here.
        $sources = $preview['sources'] ?? Cache::remember(
            'translation-catalog:'.hash('sha256', base_path().'|'.lang_path()),
            now()->addMinutes(10),
            fn () => app(TranslationScanner::class)->preview()['sources'],
        );

        return collect($sources)->map(fn ($source) => new TranslationSource($source));
    }

    #[Computed]
    public function rows()
    {
        $this->authorizeTranslation();
        $query = Translation::where('locale', $this->language->code)
            ->when($this->searchOriginal !== '', fn ($q) => $q->where(function ($q) {
                $q->where('key', 'LIKE', '%'.$this->searchOriginal.'%');
                if ($this->previewSources !== null) {
                    $q->orWhereIn('key', $this->previewSources->filter(fn ($source) => mb_stripos($source->original ?? '', $this->searchOriginal) !== false)->pluck('key')->filter()->unique()->all());
                } elseif ($this->catalogReady) {
                    $q->orWhereExists(fn ($q) => $q->selectRaw('1')->from('translation_sources')->where('active', true)->whereColumn('translation_sources.key', 'translations.key')->where('original', 'LIKE', '%'.$this->searchOriginal.'%'));
                }
            }))
            ->when($this->searchTranslate !== '', fn ($q) => $q->where('value', 'LIKE', '%'.$this->searchTranslate.'%'))
            ->when($this->completion === 'empty', fn ($q) => $q->where(fn ($q) => $q->whereNull('value')->orWhere('value', '')))
            ->when($this->completion === 'filled', fn ($q) => $q->whereNotNull('value')->where('value', '!=', ''));
        if ($this->catalogReady && ($this->surface !== '' || $this->group !== '' || $this->sourceSearch !== '')) {
            if ($this->group === '@json') {
                if ($this->previewSources !== null) {
                    $query->whereNotIn('key', $this->previewSources->where('kind', 'php')->pluck('key')->filter()->unique()->all());
                } else {
                    $query->whereNotExists(fn ($q) => $q->selectRaw('1')->from('translation_sources')->where('active', true)->where('kind', 'php')->whereColumn('translation_sources.key', 'translations.key'));
                }
            }
            if ($this->previewSources !== null) {
                if ($this->surface !== '' || ($this->group !== '' && $this->group !== '@json') || $this->sourceSearch !== '') {
                    $keys = $this->previewSources->filter(fn ($source) => ($this->surface === '' || $source->surface === $this->surface)
                        && ($this->group === '' || $this->group === '@json' || $source->group === $this->group)
                        && ($this->sourceSearch === '' || mb_stripos($source->file.' '.implode(' ', $source->routes), $this->sourceSearch) !== false));
                    $query->whereIn('key', $keys->pluck('key')->filter()->unique()->all());
                }
            } elseif ($this->surface !== '' || ($this->group !== '' && $this->group !== '@json') || $this->sourceSearch !== '') {
                $query->whereExists(function ($q) {
                    $q->selectRaw('1')->from('translation_sources')->where('active', true)->whereColumn('translation_sources.key', 'translations.key')
                        ->when($this->surface !== '', fn ($q) => $q->where('surface', $this->surface))
                        ->when($this->group !== '' && $this->group !== '@json', fn ($q) => $q->where('group', $this->group))
                        ->when($this->sourceSearch !== '', fn ($q) => $q->where(fn ($q) => $q->where('file', 'LIKE', '%'.$this->sourceSearch.'%')->orWhere('routes', 'LIKE', '%'.$this->sourceSearch.'%')));
                });
            }
        }

        return $query->orderBy('key')->paginate(40);
    }

    #[Computed]
    public function usages()
    {
        $this->authorizeTranslation();

        if ($this->previewSources !== null) {
            return $this->previewSources->whereIn('key', $this->rows->pluck('key'))->sortBy('file')->groupBy('key');
        }

        return $this->catalogReady
            ? TranslationSource::where('active', true)->whereIn('key', $this->rows->pluck('key'))->orderBy('file')->get()->groupBy('key')
            : collect();
    }

    #[Computed]
    public function groups(): array
    {
        $this->authorizeTranslation();
        if (! $this->catalogReady) {
            return [];
        }
        $keys = Translation::where('locale', $this->language->code)->select('key');
        $groups = $this->previewSources !== null
            ? $this->previewSources->whereIn('key', $keys->pluck('key'))->pluck('group')->filter()->unique()->sort()->values()->all()
            : TranslationSource::where('active', true)->whereNotNull('group')->whereIn('key', $keys)->distinct()->orderBy('group')->pluck('group')->all();

        return ['@json' => __('translation_manager.json_group')] + array_combine($groups, $groups);
    }

    #[Computed]
    public function publishedFile(): array
    {
        $this->authorizeTranslation();
        $name = $this->language->code.'.json';
        if ($this->group !== '' && $this->group !== '@json' && array_key_exists($this->group, $this->groups) && preg_match('/^[A-Za-z0-9_-]+$/D', $this->group)) {
            $name = $this->language->code.'/'.$this->group.'.php';
        }
        $safeLocale = preg_match('/^[a-z]{2,3}(?:[_-][A-Za-z]{2,4})?$/D', $this->language->code);
        $path = lang_path($name);
        clearstatcache(true, $path);

        return ['name' => $name, 'date' => $safeLocale && is_file($path) ? dateFormat(Carbon::createFromTimestamp(filemtime($path))->toIso8601String(), 'd/m/Y H:i:s') : null];
    }

    public function resetFilters(): void
    {
        $this->authorizeTranslation();
        $this->reset('searchOriginal', 'searchTranslate', 'surface', 'group', 'sourceSearch', 'completion');
        $this->resetPage();
    }

    #[Computed]
    public function counts(): array
    {
        $this->authorizeTranslation();
        $count = Translation::where('locale', $this->language->code)->selectRaw("COUNT(*) AS total, SUM(CASE WHEN value IS NOT NULL AND value != '' THEN 1 ELSE 0 END) AS filled")->first();

        return ['total' => (int) $count->total, 'filled' => (int) $count->filled];
    }

    public function updated(string $key, mixed $value): void
    {
        $this->authorizeTranslation();
        if (in_array($key, ['searchOriginal', 'searchTranslate', 'surface', 'group', 'sourceSearch', 'completion'], true)) {
            $this->resetPage();

            return;
        }
        if (! preg_match('/^translations\.([0-9]+)\.value$/D', $key, $matches)) {
            return;
        }
        abort_unless(is_string($value) && strlen($value) <= 65535, 422);
        $translation = Translation::where('locale', $this->language->code)->findOrFail((int) $matches[1]);
        $original = $this->previewSources !== null ? $this->previewSources->firstWhere('key', $translation->key)?->original
            : ($this->catalogReady ? TranslationSource::where('key', $translation->key)->where('active', true)->value('original') : null);
        $original ??= $translation->key;
        preg_match_all('/(?<!:):[A-Za-z_][A-Za-z0-9_]*/', $original, $expected);
        preg_match_all('/(?<!:):[A-Za-z_][A-Za-z0-9_]*/', $value, $actual);
        $expected = array_unique($expected[0]);
        $actual = array_unique($actual[0]);
        sort($expected);
        sort($actual);
        if (filled($value) && $expected !== $actual) {
            throw ValidationException::withMessages([$key => __('translation_manager.placeholders_error')]);
        }
        $this->resetErrorBag($key);
        $translation->update(['value' => trim($value)]);
    }

    public function previewScan(): void
    {
        $this->authorizeTranslation();
        abort_unless($this->catalogReady, 409);
        if ($this->scanToken !== '') {
            Cache::forget($this->scanCacheKey());
        }
        $preview = app(TranslationScanner::class)->preview();
        $this->scanToken = (string) Str::uuid();
        Cache::put($this->scanCacheKey(), $preview, now()->addMinutes(10));
        $this->scanSummary = array_diff_key($preview, ['sources' => true]);
        $this->scanSummary['applied'] = false;
    }

    private function scanCacheKey(): string
    {
        return 'translation-scan:'.auth()->id().':'.$this->scanToken;
    }

    public function applyScan(): void
    {
        $this->authorizeTranslation();
        abort_unless($this->catalogReady, 409);
        $preview = Cache::get($this->scanCacheKey());
        if (! $preview) {
            throw ValidationException::withMessages(['scan' => __('translation_manager.preview_expired')]);
        }
        $this->scanSummary = app(TranslationScanner::class)->apply($preview);
        $this->scanSummary['applied'] = true;
        Cache::forget($this->scanCacheKey());
        $this->resetPage();
    }

    #[Computed]
    public function dynamicSources()
    {
        $this->authorizeTranslation();
        if ($this->previewSources !== null) {
            return $this->previewSources->whereNull('key')->sortBy('file')->take(30);
        }

        return $this->catalogReady ? TranslationSource::where('active', true)->whereNull('key')->orderBy('file')->limit(30)->get() : collect();
    }

    public function save()
    {
        $this->authorizeTranslation();
        TranslationHelper::build($this->language->code, Translation::where('locale', $this->language->code)->pluck('value', 'key')->all());

        return redirect()->route('admin.languages.index')->with(['status' => 'success', 'message' => __('Çeviriler kayıt edildi!')]);
    }

    public function render()
    {
        $this->authorizeTranslation();
        $this->translations = $this->rows->mapWithKeys(fn ($row) => [$row->id => ['value' => $row->value ?? '']])->all();

        return view('livewire.backend.languages.language-translate-form');
    }
}
