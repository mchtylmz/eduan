<?php

namespace App\Livewire\Frontend\Lessons;

use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy]
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
        return Cache::remember(
            implode('_', [
                'frontend_topics', ($this->lesson->id ?? 0), $this->word
            ]),
            60 * 60 * 60 * 365,
            function () {
                return Topic::when(!empty($this->lesson->id), fn($query) => $query->where('lesson_id', $this->lesson->id))
                    ->when($this->search, function ($query) {
                        $query->where(function ($query) {
                            $query->where('code', 'like', "%{$this->word}%")
                                ->orWhere('title', 'like', "%{$this->word}%");
                        });
                    })
                    ->with('exams')
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
