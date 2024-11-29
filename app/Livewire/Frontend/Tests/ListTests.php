<?php

namespace App\Livewire\Frontend\Tests;

use App\Actions\Exams\UpdateExamFavoriteForUserAction;
use App\Enums\ReviewVisibilityEnum;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy(isolate: true)]
class ListTests extends Component
{
    use WithPagination, CustomLivewireAlert;

    protected string $paginationTheme = 'bootstrap';

    public bool $showHits = false;
    public bool $showPaginate = true;
    public int $paginate = 21;
    public bool $showSearch = true;

    public bool $search = false;
    public string $word = '';
    public int $lesson_id = 0;
    public int $topic_id = 0;

    public array $userFavoritesTests = [];

    public function mount(int $topic_id = 0): void
    {
        $this->topic_id = $topic_id;

        if (auth()->check()) {
            $this->userFavoritesTests = auth()->user()?->favoriteExams->pluck('id')->toArray();
        }
    }

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
    public function tests()
    {
        return Cache::remember(
            implode('_', [
                'frontend_tests',
                ($this->lesson_id ?? 0),
                ($this->topic_id ?? 0),
                $this->word,
                intval($this->showHits),
                $this->paginate
            ]),
            60 * 60 * 60 * 365,
            function () {
                return Exam::withCount([
                        'questions' => fn($query) => $query->active(),
                        'reviews' => fn($query) => $query->where('visibility', ReviewVisibilityEnum::PUBLIC),
                    ])
                    ->with(['language', 'userResults'])
                    ->when(!empty($this->lesson_id), function ($query) {
                        $query->whereHas('lessons', function ($query) {
                            return $query->where('id', $this->lesson_id)->active();
                        });
                    })
                    ->when(!empty($this->topic_id), function ($query) {
                        $query->whereHas('topics', function ($query) {
                            return $query->where('id', $this->topic_id)->active();
                        });
                    })
                    ->when($this->search, function ($query) {
                        return $query->where(function ($query) {
                            return $query->where('code', 'like', "%{$this->word}%")
                                ->orWhere('name', 'like', "%{$this->word}%");
                        });
                    })
                    ->when(!$this->showHits, function ($query) {
                        $query->orderByDesc('hits');
                    })
                    ->active()
                    ->paginate($this->paginate);
            }
        );
    }

    public function toggleBookmark(Exam $exam): void
    {
        $isAttach = !in_array($exam->id, $this->userFavoritesTests);

        UpdateExamFavoriteForUserAction::run(
            exam: $exam,
            user: User::find(auth()->id()),
            action: $isAttach ? 'attach' : 'detach'
        );

        if ($isAttach) {
            $this->userFavoritesTests[] = $exam->id;
        } else {
            $this->userFavoritesTests = collect($this->userFavoritesTests)
                ->filter(function ($test_id) use ($exam) {
                    return $test_id != $exam->id;
                })->values()->toArray();
        }

        $this->message(
            $isAttach ? __('Seçilen test favori olarak eklendi.') : __('Seçilen test favoriden çıkarıldı.')
        )->success();
    }

    public function render()
    {
        return view('livewire.frontend.tests.list-tests');
    }
}
