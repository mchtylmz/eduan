<?php

namespace App\Livewire\Frontend\Blogs;

use App\Helpers\FrontendCache;
use App\Models\Blog;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ListBlogs extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public bool $showHits = false;

    public bool $showPaginate = true;

    public int $paginate = 18;

    public bool $grid = false;

    public string $search = '';

    #[Computed]
    public function blogs()
    {
        return FrontendCache::paginate(
            'blogs',
            ['search' => $this->search, 'hits' => $this->showHits, 'limit' => $this->paginate, 'page' => $this->getPage()],
            function () {
                return Blog::select(['id', 'locale', 'slug', 'image', 'title', 'brief', 'hits', 'published_at', 'status'])->where('locale', app()->getLocale())
                    ->where('published_at', '<=', now())
                    ->when($this->showHits, fn ($query) => $query->orderByDesc('hits'))
                    ->when(
                        $this->search,
                        fn ($query) => $query->where(function ($query) {
                            $query->where('title', 'like', '%'.$this->search.'%')
                                ->orWhere('content', 'like', '%'.$this->search.'%');
                        })
                    )
                    ->active()
                    ->orderByDesc('published_at')
                    ->paginate($this->paginate);
            },
            fn () => Blog::where('locale', app()->getLocale())->active()->where('published_at', '>', now())->min('published_at')
        );
    }

    public function render()
    {
        if ($this->grid) {
            return view('livewire.frontend.blogs.grid-blogs');
        }

        return view('livewire.frontend.blogs.list-blogs');
    }
}
