<?php

namespace App\Livewire\Blogs;

use App\Actions\Blogs\CreateOrUpdateBlogAction;
use App\Actions\Files\UploadFileAction;
use App\Actions\Files\UploadThumbnailAction;
use App\Enums\StatusEnum;
use App\Models\Blog;
use App\Models\Faq;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class BlogForm extends Component
{
    use WithFileUploads;

    public Blog $blog;

    public string $locale;
    public string $slug;
    public string $title;
    public string $brief = '';
    public string $content;
    public string $keywords = '';
    public StatusEnum $status = StatusEnum::ACTIVE;
    public string $published_at = '';
    public $image;

    public string $permission = 'blogs:add';

    public function mount(int $blogId = 0)
    {
        $this->locale = app()->getLocale();
        $this->published_at = now()->addMinutes(3)->format('Y-m-d H:i');

        if ($blogId && $this->blog = Blog::find($blogId)) {
            $this->locale = $this->blog->locale;
            $this->slug = $this->blog->slug;
            $this->title = $this->blog->title;
            $this->brief = $this->blog->brief;
            $this->content = html_entity_decode($this->blog->content);
            $this->keywords = $this->blog->keywords;
            $this->status = $this->blog->status;
            $this->published_at = $this->blog->published_at?->format('Y-m-d H:i');

            $this->permission = 'blogs:update';
        }
    }

    public function updatedTitle(): void
    {
        $this->slug = Str::slug($this->title);
    }

    public function rules(): array
    {
        return [
            'locale' => 'required|string|exists:languages,code',
            'slug' => ['required', 'string', Rule::unique('blogs', 'slug')->ignore($this->blog->id ?? 0)],
            'title' => 'required|string',
            'brief' => 'nullable|string',
            'content' => 'required|string',
            'keywords' => 'nullable|string',
            'published_at' => 'required|date|date_format:Y-m-d H:i',
            'image' => 'nullable|image',
            'status' => [
                'required',
                new Enum(StatusEnum::class)
            ]
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'locale' => __('Dil'),
            'slug' => __('Blog Başlığı'),
            'title' => __('Başlık'),
            'brief' => __('Kısa Açıklama'),
            'content' => __('İçerik'),
            'keywords' => __('Anahtar Kelimeler'),
            'published_at' => __('Paylaşım Zamanı'),
            'image' => __('Görsel'),
            'status' => __('Durum')
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'locale' => $this->locale,
            'slug' => $this->slug,
            'title' => $this->title,
            'brief' => $this->brief,
            'content' => htmlentities($this->content),
            'keywords' => $this->keywords,
            'status' => $this->status,
            'published_at' => $this->published_at
        ];
        if ($this->image instanceof TemporaryUploadedFile) {
            $data['image'] = UploadFileAction::run(file: $this->image, folder: 'blogs');
        }

        CreateOrUpdateBlogAction::run(
            data: $data,
            blog: !empty($this->blog) && $this->blog->exists ? $this->blog : null
        );

        return redirect()->route('admin.blogs.index')->with([
            'status' => 'success',
            'message' => __('Blog kayıt edildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.blogs.blog-form');
    }
}
