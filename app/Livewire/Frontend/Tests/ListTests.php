<?php

namespace App\Livewire\Frontend\Tests;

use App\Actions\Exams\UpdateExamFavoriteForUserAction;
use App\Enums\ReviewVisibilityEnum;
use App\Helpers\FrontendCache;
use App\Models\Exam;
use App\Models\ExamResultDetail;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ListTests extends Component
{
    use CustomLivewireAlert, WithPagination;

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
            $this->userFavoritesTests = auth()->user()->favoriteExams()->pluck('exams.id')->all();
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
        $tests = clone FrontendCache::paginate(
            'tests',
            ['lesson' => $this->lesson_id, 'topic' => $this->topic_id, 'search' => $this->search ? $this->word : '', 'hits' => $this->showHits, 'limit' => $this->paginate, 'page' => $this->getPage()],
            function () {
                return Exam::select(['id', 'code', 'name', 'locale', 'visibility', 'hits', 'status'])->withCount([
                    'questions' => fn ($query) => $query->active(),
                    'reviews' => fn ($query) => $query->where('visibility', ReviewVisibilityEnum::PUBLIC),
                ])
                    ->with('language:code,name')
                    ->when(! empty($this->lesson_id), function ($query) {
                        $query->whereHas('lessons', function ($query) {
                            return $query->where('id', $this->lesson_id)->active();
                        });
                    })
                    ->when(! empty($this->topic_id), function ($query) {
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
                    ->when(! $this->showHits, function ($query) {
                        $query->orderByDesc('hits');
                    })
                    ->active()
                    ->paginate($this->paginate);
            }
        );
        $tests->setCollection($tests->getCollection()->map(fn ($test) => clone $test));
        if (auth()->check() && $tests->count()) {
            $tests->getCollection()->loadCount([
                'results as personal_results_count' => fn ($query) => $query->where('user_id', auth()->id()),
            ]);
            $stats = ExamResultDetail::query()
                ->join('exam_results', 'exam_results.id', '=', 'exam_result_details.exam_result_id')
                ->where('exam_results.user_id', auth()->id())
                ->whereIn('exam_results.exam_id', $tests->getCollection()->modelKeys())
                ->selectRaw('exam_results.exam_id, SUM(exam_result_details.correct = 1) AS correct_count, SUM(exam_result_details.correct = 0) AS incorrect_count')
                ->groupBy('exam_results.exam_id')->get()->keyBy('exam_id');
            foreach ($tests as $test) {
                $test->setAttribute('personal_correct_count', (int) ($stats->get($test->id)?->correct_count ?? 0));
                $test->setAttribute('personal_incorrect_count', (int) ($stats->get($test->id)?->incorrect_count ?? 0));
            }
        }

        return $tests;
    }

    public function toggleBookmark(Exam $exam): bool
    {
        if (! auth()->check()) {
            $this->message(__('Üye girişi yapılmalıdır.'))->error();

            return false;
        }

        $isAttach = ! in_array($exam->id, $this->userFavoritesTests);

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

        return true;
    }

    public function render()
    {
        return view('livewire.frontend.tests.list-tests');
    }
}
