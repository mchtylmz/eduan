<?php

namespace App\Livewire\Languages;

use App\Helpers\TranslationHelper;
use App\Models\Language;
use App\Models\Translation;
use Druc\Langscanner\CachedFileTranslations;
use Druc\Langscanner\FileTranslations;
use Druc\Langscanner\Languages;
use Druc\Langscanner\MissingTranslations;
use Druc\Langscanner\RequiredTranslations;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy(isolate: true)]
class LanguageTranslateForm extends Component
{
    use WithPagination;

    public Language $language;

    public array $translations = [];
    public string $permission = 'languages:translate';

    public string $searchOriginal = '';
    public string $searchTranslate = '';

    public function mount(int $languageId = 0): void
    {
        $this->language = Language::find($languageId);

        if (!(new TranslationHelper)->exists($this->language->code)) {
            $translations = (new TranslationHelper)->scan($this->language->code);
            TranslationHelper::upload($this->language->code, $translations);
        }

        $this->translations = $this->language->translations->mapWithKeys(function ($item) {
            return [$item->id => $item];
        })->toArray();
    }

    #[Computed]
    public function translations()
    {
        return Translation::where('locale', $this->language->code)
            ->when($this->searchOriginal, fn ($query) => $query->where('key', 'LIKE', '%'.$this->searchOriginal.'%'))
            ->when($this->searchTranslate, fn ($query) => $query->where('value', 'LIKE', '%'.$this->searchTranslate.'%'))
            ->orderBy('id')
            ->paginate(40);
    }

    public function updated(string $key, string $value): void
    {
        $index = intval(str_replace(['translations', '.', 'value'], '', $key));

        if ($id = data_get($this->translations, $index.'.id')) {
            Translation::where('id', $id)->update(['value' => $value]);
        }
    }

    public function translationsCount(): int
    {
        return collect($this->translations)->count();
    }

    public function translatedCount(): int
    {
        return collect($this->translations)->filter(fn($item) => $item['value'] != '')->count();
    }

    public function save()
    {
        $translations = Translation::where('locale', $this->language->code)
            ->orderBy('id')
            ->get()
            ->keyBy('key')
            ->map(fn($item) => $item->value)
            ->toArray();

        TranslationHelper::build($this->language->code, $translations);

        return redirect()->route('admin.languages.index')->with([
            'status' => 'success',
            'message' => __('Çeviriler kayıt edildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.languages.language-translate-form');
    }
}
