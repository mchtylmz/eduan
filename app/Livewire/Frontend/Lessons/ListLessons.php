<?php

namespace App\Livewire\Frontend\Lessons;

use App\Helpers\FrontendCache;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ListLessons extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public bool $showHits = false;

    public bool $showPaginate = true;

    public int $paginate = 21;

    public bool $showSearch = true;

    public bool $visibilityLite = false;

    public bool $home = false;

    public bool $search = false;

    public string $word = '';

    public function searchLesson(): void
    {
        $this->search = true;
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = false;
        $this->word = '';
        $this->resetPage();
    }

    #[Computed]
    public function lessons()
    {
        return FrontendCache::paginate(
            'lessons',
            ['search' => $this->search ? $this->word : '', 'hits' => $this->showHits, 'limit' => $this->paginate, 'page' => $this->getPage()],
            function () {
                return Lesson::select(['id', 'code', 'name', 'description', 'sort', 'hits', 'status'])->withCount([
                    'topics' => fn ($query) => $query->active(),
                    'exams' => fn ($query) => $query->active()->select(DB::raw('COUNT(DISTINCT exams.id)')),
                ])
                    ->when($this->search, function ($query) {
                        $query->where(function ($query) {
                            $query->where('code', 'like', "%{$this->word}%")
                                ->orWhere('name', 'like', "%{$this->word}%");
                        });
                    })
                    ->when(! $this->showHits, function ($query) {
                        $query->orderByDesc('hits');
                    })
                    ->active()
                    ->orderBy('sort')
                    ->paginate($this->paginate);
            }
        );
    }

    public function render()
    {
        return view('livewire.frontend.lessons.list-lessons');
    }
}
