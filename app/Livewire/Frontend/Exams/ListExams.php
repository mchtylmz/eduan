<?php

namespace App\Livewire\Frontend\Exams;

use App\Helpers\FrontendCache;
use App\Models\Test;
use App\Traits\CustomLivewireAlert;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ListExams extends Component
{
    use CustomLivewireAlert, WithPagination;

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
        return FrontendCache::paginate(
            'exams',
            ['include_passive' => true, 'search' => $this->search ? $this->word : '', 'hits' => $this->showHits, 'limit' => $this->paginate, 'page' => $this->getPage()],
            function () {
                return Test::select(['id', 'code', 'name', 'locale', 'duration', 'hits', 'status'])->withCount(['sections_with_no_parent', 'questions'])
                    ->with('language:code,name')
                    ->when($this->search, function ($query) {
                        return $query->where(function ($query) {
                            $upperWord = mb_strtoupper($this->word, 'UTF-8');
                            $lowerWord = mb_strtolower($this->word, 'UTF-8');

                            return $query->where('code', 'like', "%{$this->word}%")
                                ->orWhere('name', 'like', "%{$upperWord}%")
                                ->orWhere('name', 'like', "%{$lowerWord}%")
                                ->orWhere('content', 'like', "%{$this->word}%");
                        });
                    })
                    ->when(! $this->showHits, function ($query) {
                        $query->orderByDesc('hits');
                    })
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
