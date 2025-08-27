<?php

namespace App\Livewire\Frontend\Exams;

use App\Models\Test;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy(isolate: true)]
class ListExams extends Component
{
    use WithPagination, CustomLivewireAlert;

    protected string $paginationTheme = 'bootstrap';

    public bool $showHits = true;
    public bool $showPaginate = true;
    public int $paginate = 21;
    public bool $showSearch = true;

    public bool $search = false;
    public string $word = '';

    public function searchExams(): void
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
    public function exams()
    {
        return Cache::remember(
            implode('_', [
                'frontend_exams',
                $this->word,
                intval($this->showHits),
                $this->paginate,
                request('page', 1),
                auth()->check() ? auth()->id() : 'not_auth',
            ]),
            60 * 60 * 30, // 1 ay
            function () {
                return Test::withCount(['sections_with_no_parent', 'questions'])
                    ->with(['language'])
                    ->when($this->search, function ($query) {
                        return $query->where(function ($query) {
                            $upperWord = mb_strtoupper($this->word, 'UTF-8');;
                            $lowerWord = mb_strtolower($this->word, 'UTF-8');;

                            return $query->where('code', 'like', "%{$this->word}%")
                                ->orWhere('name', 'like', "%{$upperWord}%")
                                ->orWhere('name', 'like', "%{$lowerWord}%")
                                ->orWhere('content', 'like', "%{$this->word}%");
                        });
                    })
                    ->when(!$this->showHits, function ($query) {
                        $query->orderByDesc('hits');
                    })
                    // ->active()
                    ->having('sections_with_no_parent_count', '>', 0)
                    ->having('questions_count', '>', 0)
                    ->paginate($this->paginate);
            });
    }

    public function render()
    {
        return view('livewire.frontend.exams.list-exams');
    }
}
