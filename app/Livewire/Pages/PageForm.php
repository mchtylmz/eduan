<?php

namespace App\Livewire\Pages;

use App\Actions\Pages\CreateOrUpdatePageAction;
use App\Enums\PageTypeEnum;
use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Component;

class PageForm extends Component
{
    public Page $page;

    public string $slug;
    public array $title;
    public array $brief = [];
    public array $content = [];
    public array $keywords = [];
    public array $link = [];
    public int $sort = 1;
    public StatusEnum $status = StatusEnum::ACTIVE;
    public YesNoEnum $linkStatus = YesNoEnum::NO;

    public string $permission = 'pages:add';

    public function mount(int $pageId = 0)
    {
        if ($pageId && $this->page = Page::find($pageId)) {
            $this->slug = $this->page->slug;
            $this->title = $this->page->getTranslations('title');
            $this->brief = $this->page->getTranslations('brief');
            $this->content = $this->page->getTranslations('content');
            $this->keywords = $this->page->getTranslations('keywords');
            $this->sort = $this->page->sort;
            $this->status = $this->page->status;
            $this->link = $this->page->getTranslations('link');
            $this->linkStatus = !empty($this->link) ? YesNoEnum::YES : YesNoEnum::NO;

            $this->permission = 'pages:update';
        }
    }

    public function updated(string $key, mixed $value): void
    {
        if (str_contains($key, 'title.'.settings()->defaultLocale)) {
            $this->slug = Str::slug($value);
        }
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', Rule::unique('pages', 'slug')->ignore($this->page->id ?? 0)],
            'title' => 'required|array|min:1',
            'brief' => 'nullable|array',
            'content' => 'nullable|array',
            'keywords' => 'nullable|array',
            'link' => [Rule::when(YesNoEnum::YES->is($this->linkStatus), 'required|array', 'nullable')],
            'sort' => 'required|integer',
            'status' => [
                'required',
                new Enum(StatusEnum::class)
            ]
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'slug' => __('Slug (URL)'),
            'title' => __('Başlık'),
            'brief' => __('Kısa Açıklama'),
            'content' => __('İçerik'),
            'keywords' => __('Anahtar Kelimeler'),
            'link' => __('Yönlendirme Linki'),
            'sort' => __('Sıra'),
            'status' => __('Durum')
        ];
    }

    public function save()
    {
        $this->validate();

        CreateOrUpdatePageAction::run(
            data: [
                'slug' => Str::slug($this->slug),
                'title' => $this->title,
                'brief' => $this->brief,
                'content' => $this->content ?? [],
                'keywords' => $this->keywords ?? [],
                'link' => $this->link ?? [],
                'sort' => $this->sort,
                'status' => $this->status,
                'type' => PageTypeEnum::CUSTOM
            ],
            page: !empty($this->page) && $this->page->exists ? $this->page : null,
        );

       resetCache();

        return redirect()->route('admin.pages.all')->with([
            'status' => 'success',
            'message' => __('Sayfa bilgisi edildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.pages.page-form');
    }
}
