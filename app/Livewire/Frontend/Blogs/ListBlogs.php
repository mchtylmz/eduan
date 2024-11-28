<?php

namespace App\Livewire\Frontend\Blogs;

use App\Models\Blog;
use App\Models\Lesson;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy(isolate: true)]
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
        return Cache::remember(
            implode('_', [
                'frontend_blogs', app()->getLocale(), intval($this->showHits), $this->paginate, $this->search
            ]),
            60 * 60 * 3,
            function () {
                return Blog::where('locale', app()->getLocale())
                    ->where('published_at', '<=', now())
                    ->when($this->showHits, fn($query) => $query->orderByDesc('hits'))
                    ->when(
                        $this->search,
                        fn($query) => $query->where(function ($query) {
                            $query->where('title', 'like', '%' . $this->search . '%')
                                ->orWhere('content', 'like', '%' . $this->search . '%');
                        })
                    )
                    ->active()
                    ->orderByDesc('published_at')
                    ->paginate($this->paginate);
            }
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
