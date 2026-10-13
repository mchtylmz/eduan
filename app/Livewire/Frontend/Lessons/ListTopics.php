<?php

namespace App\Livewire\Frontend\Lessons;

use App\Helpers\FrontendCache;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ListTopics extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public Lesson $lesson;

    public bool $visibilityLite = false;

    public bool $search = false;

    public string $word = '';

    public function searchTopics(): void
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
    public function topics()
    {
        return FrontendCache::paginate(
            'topics',
            ['lesson' => $this->lesson->id, 'search' => $this->search ? $this->word : '', 'page' => $this->getPage()],
            function () {
                return Topic::select(['id', 'code', 'lesson_id', 'title', 'description', 'sort', 'hits', 'status'])->when(! empty($this->lesson->id), fn ($query) => $query->where('lesson_id', $this->lesson->id))
                    ->when($this->search, function ($query) {
                        $query->where(function ($query) {
                            $query->where('code', 'like', "%{$this->word}%")
                                ->orWhere('title', 'like', "%{$this->word}%");
                        });
                    })
                    ->with('lesson:id,name')
                    ->withCount(['exams' => fn ($query) => $query->select(DB::raw('COUNT(DISTINCT exams.id)'))])
                    ->active()
                    ->orderBy('sort')
                    ->paginate(21);
            }
        );
    }

    public function render()
    {
        return view('livewire.frontend.lessons.list-topics');
    }
}
