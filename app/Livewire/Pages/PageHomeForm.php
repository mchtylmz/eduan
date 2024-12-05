<?php

namespace App\Livewire\Pages;

use App\Actions\Pages\UpdateHomePageAction;
use App\Enums\PageMenuEnum;
use App\Enums\StatusEnum;
use App\Models\Page;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PageHomeForm extends Component
{
    use CustomLivewireAlert;

    public Page $page;

    public array $title = [];
    public array $brief = [];
    public array $content = [];
    public array $keywords = [];

    public string $slug;
    public int $sort = 1;
    public StatusEnum $status = StatusEnum::ACTIVE;
    public string $permission = 'pages:update';

    public function mount()
    {
        $this->page = Page::where('menu', PageMenuEnum::HOME)->first();

        $this->title = $this->page->getTranslations('title');
        $this->brief = $this->page->getTranslations('brief');
        $this->content = $this->page->getTranslations('content');
        $this->keywords = $this->page->getTranslations('keywords');
        $this->slug = $this->page->slug;
        $this->sort = $this->page->sort;
        $this->status = $this->page->status;
    }

    public function updated(string $key, string $value): void
    {
        if (str_ends_with($key, settings()->defaultLocale)) {
            $this->slug = Str::slug($value);
        }
    }

    public function rules(): array
    {
        return [
            'slug' => [
                'required', 'string', 'min:3', Rule::unique('pages', 'slug')
                    ->ignore($this->page->id ?? 0)
            ],
            'title' => 'required|array|min:1',
            'content' => 'required|array|min:1',
            'keywords' => 'nullable|array'
        ];
    }

    public function getValidationAttributes(): array
    {
        return [
            'slug' => __('Slug (URL)'),
            'title.*' => __('Başlık'),
            'content.*' => __('Açıklama'),
            'keywords.*' => __('Anahtar Kelimeler'),
        ];
    }

    public function save()
    {
        $this->validate();

        UpdateHomePageAction::run(data: [
            'title' => $this->title,
            'brief' => $this->brief,
            'content' => $this->content,
            'keywords' => $this->keywords,
            'slug' => Str::slug($this->slug),
            'status' => $this->status,
            'sort' => $this->sort,
        ]);

       resetCache();

        return redirect()->route('admin.pages.home')->with([
            'status' => 'success',
            'message' => __('Sayfa bilgileri kayıt edildi!')
        ]);
    }


    public function render()
    {
        return view('livewire.backend.pages.page-home-form');
    }
}
