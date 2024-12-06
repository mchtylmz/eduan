<?php

namespace App\Livewire\Frontend\Lessons;

use App\Filters\LessonFilter;
use App\Models\Faq;
use App\Models\Lesson;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy]
class ListLessons extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public bool $showHits = false;
    public bool $showPaginate = true;
    public int $paginate = 21;
    public bool $showSearch = true;

    public bool $visibilityLite = false;
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
        return Cache::remember(
            implode('_', [
                'frontend_lessons', $this->word, intval($this->showHits), $this->paginate
            ]),
            60 * 60 * 60 * 365,
            function () {
                return Lesson::withCount([
                        'topics' => fn($query) => $query->active(),
                        'exams' => fn($query) => $query->active()
                    ])
                    ->with([
                        'exams' => fn($query) => $query->active()
                    ])
                    ->when($this->search, function ($query) {
                        $query->where('code', 'like', "%{$this->word}%")
                            ->orWhere('name', 'like', "%{$this->word}%");
                    })
                    ->when(!$this->showHits, function ($query) {
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
