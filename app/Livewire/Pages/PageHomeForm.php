<?php

namespace App\Livewire\Pages;

use App\Actions\Files\UploadFileAction;
use App\Actions\Pages\UpdateHomePageAction;
use App\Enums\PageMenuEnum;
use App\Enums\StatusEnum;
use App\Models\Page;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class PageHomeForm extends Component
{
    use CustomLivewireAlert, WithFileUploads;

    public Page $page;

    public array $title = [];
    public array $brief = [];
    public array $content = [];
    public array $keywords = [];
    public array $imageTranslations = [];

    public string $slug;
    public int $sort = 1;
    public StatusEnum $status = StatusEnum::ACTIVE;
    public string $permission = 'pages:update';

    public $images = [];

    public function mount(): void
    {
        $this->page = Page::where('menu', PageMenuEnum::HOME)->first();

        $this->title = $this->page->getTranslations('title');
        $this->brief = $this->page->getTranslations('brief');
        $this->content = $this->page->getTranslations('content');
        $this->keywords = $this->page->getTranslations('keywords');
        $this->imageTranslations = $this->page->getTranslations('images');
        $this->slug = $this->page->slug;
        $this->sort = $this->page->sort;
        $this->status = $this->page->status;
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

    public function validationAttributes(): array
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

        $data = [
            'title' => $this->title,
            'brief' => $this->brief,
            'content' => $this->content,
            'keywords' => $this->keywords,
            'slug' => Str::slug($this->slug),
            'status' => $this->status,
            'sort' => $this->sort
        ];

        if ($this->images) {
            $data['images'] = array_merge(collect(data()->languages())->mapWithKeys(function ($item) {
                return [$item['code'] => []];
            })->toArray(), $this->imageTranslations);

            foreach ($data['images'] as $locale => $images) {
                if (empty($this->images[$locale]))
                    continue;

                foreach ($this->images[$locale] as $key => $image) {
                    $data['images'][$locale][$key] = UploadFileAction::run(file: $image, folder: 'pages');
                }
            }
        }

        UpdateHomePageAction::run(data: $data);

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
