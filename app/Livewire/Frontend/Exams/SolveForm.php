<?php

namespace App\Livewire\Frontend\Exams;

use App\Actions\Tests\CompleteTestResult;
use App\Actions\Tests\SaveTestResultAnswer;
use App\Actions\Tests\StartTestResult;
use App\Enums\StatusEnum;
use App\Enums\TestSectionTypeEnum;
use App\Enums\YesNoEnum;
use App\Models\Test;
use App\Models\TestsResult;
use App\Models\TestsSection;
use App\Traits\CustomLivewireAlert;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class SolveForm extends Component
{
    use CustomLivewireAlert, WithoutUrlPagination, WithPagination;

    #[Locked]
    public Test $test;

    #[Locked]
    public TestsResult $testResult;

    public TestsSection $content;

    #[Locked]
    public Collection $sections;

    #[Locked]
    public array $active = [];

    #[Locked]
    public array $sectionHistory = [];

    #[Locked]
    public array $sectionNavigation = [];

    public array $results = [];
    /*
     results = [
        section_index => [
            parent_id => [
                question_id => answer_id
            ],
            parent_id => [
                question_id => answer_id
            ]
        ]
    ]
    */

    public string $sectionTitle = '';

    #[Locked]
    public int $totalQuestionsCount = 0;

    #[Locked]
    public string $expirationTime = '';

    #[Locked]
    public bool $finished = false;

    protected $listeners = [
        'saveAndFinish' => 'saveAndFinish',
    ];

    public function mount(Test $test): void
    {
        abort_unless(auth()->user()?->can('tests:solve'), 403);
        abort_unless(StatusEnum::ACTIVE->is($test->status), 403);

        $this->test = $test;
        if (! $this->test->sections_with_no_parent()->count() || ! $this->test->questions()->count()) {
            redirect()->route('frontend.exams');

            return;
        }

        $this->sections = $test->sections()->parentIsZero()->with('parents.question')->get();
        $this->initializeSectionNavigation();

        $this->totalQuestionsCount = $test->questions()->count();
        $firstSection = $this->sections->search(fn ($section) => $section->parents->isNotEmpty());
        if ($firstSection === false) {
            redirect()->route('frontend.exams');

            return;
        }
        $this->setActive($firstSection, $this->sections[$firstSection]->parents->first()->id);

        $this->startedExams();
        $this->initializeResultsArray();
    }

    private function initializeResultsArray(): void
    {
        $answers = $this->testResult->details()->get()->keyBy(fn ($detail) => $detail->section_id.':'.$detail->question_id);
        $this->results = [];

        collect($this->sections)->each(function ($section, $sectionIndex) use ($answers) {
            $parents = $section->parents->filter(fn ($parent) => TestSectionTypeEnum::QUESTION->is($parent->type));

            collect($parents)->each(function ($parent) use ($sectionIndex, $answers) {
                $questionId = $parent->question->question_id ?? 0;
                $this->results[$sectionIndex][$parent->id] = [
                    $questionId => $answers->get($parent->id.':'.$questionId)?->answer_id ?? 0,
                ];
            });
        });
    }

    private function initializeExpiration(): void
    {
        $this->expirationTime = $this->testResult->expires_at
            ->timezone(settings()->timezone ?? config('app.timezone'))->format('Y-m-d H:i:s');
    }

    public function setActive(int $sectionKey, int $parentId): void
    {
        $section = $this->sections->get($sectionKey);
        abort_unless($section && $section->parents()->whereKey($parentId)->exists(), 422);
        $this->active = [
            'index' => $this->sectionNavigationValue($sectionKey, $parentId),
            'section' => $sectionKey,
            'parent' => $parentId,
        ];

        $this->sectionHistory[] = $parentId;
    }

    private function sectionNavigationValue(int $sectionKey, int $parentId): string
    {
        return $sectionKey.':'.$parentId;
    }

    private function initializeSectionNavigation(): void
    {
        $this->sectionNavigation = [];
        foreach ($this->sections as $key => $section) {
            foreach ($section->parents as $parent) {
                $this->sectionNavigation[] = $this->sectionNavigationValue($key, $parent->id);
            }
        }
    }

    #[Computed]
    public function sectionLabels(): array
    {
        $labels = [];
        foreach ($this->sections as $section) {
            $number = 1;
            foreach ($section->parents as $parent) {
                $labels[$parent->id] = TestSectionTypeEnum::QUESTION->is($parent->type)
                    ? __('Soru').' '.$number++ : $parent->name;
            }
        }

        return $labels;
    }

    #[Computed]
    public function getContent()
    {
        if ($this->finished) {
            return false;
        }

        return $this->content = TestsSection::where('test_id', $this->test->id ?? 0)
            ->where('id', $this->active['parent'] ?? 0)
            ->first();
    }

    public function getParentContent(int $order)
    {
        if ($order && $activeSection = collect($this->sections)->get($this->active['section'])) {
            return $activeSection->parents()->with('meta')->where('order', $order)->first();
        }

        return null;
    }

    #[Computed]
    public function resultQuestionsCount(): int
    {
        $count = 0;
        foreach ($this->results as $section) {
            foreach ($section as $question) {
                if (array_values($question)[0]) {
                    $count++;
                }
            }
        }

        return $count;
    }

    #[Computed]
    public function resultQuestionsPercent(): int
    {
        return intval($this->resultQuestionsCount() * 100 / $this->totalQuestionsCount);
    }

    public function countHistoryValue(int $value = 0)
    {
        return array_count_values($this->sectionHistory)[$value] ?? 0;
    }

    public function isSelectedAnswer(int $questionId, int $answerId = 0): bool
    {
        $activeSection = $this->active['section'];
        $activeParent = $this->active['parent'];

        if (empty($this->results[$activeSection][$activeParent][$questionId])) {
            return false;
        }

        return $this->results[$activeSection][$activeParent][$questionId] == $answerId;
    }

    public function uncheckAnswer(int $questionId): void
    {
        $activeSection = $this->active['section'];
        $activeParent = $this->active['parent'];

        $this->results[$activeSection][$activeParent][$questionId] = 0;
        $this->persistAnswer($activeParent, $questionId, 0);
    }

    public function updatedResults(mixed $value, ?string $key = null): void
    {
        abort_unless($key && preg_match('/^[0-9]+\.([0-9]+)\.([0-9]+)$/', $key, $matches), 422);
        abort_unless((is_int($value) || is_string($value))
            && filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) !== false, 422);

        $this->persistAnswer((int) $matches[1], (int) $matches[2], (int) $value);
    }

    private function persistAnswer(int $sectionId, int $questionId, int $answerId): void
    {
        $this->sections->loadMissing('parents.question');
        $this->testResult = SaveTestResultAnswer::run($this->currentResult(), $sectionId, $questionId, $answerId);
        $this->finished = YesNoEnum::YES->is($this->testResult->completed);
        $this->initializeResultsArray();
    }

    private function currentResult(): TestsResult
    {
        abort_unless(auth()->user()?->can('tests:solve'), 403);

        return TestsResult::whereKey($this->testResult->id)
            ->where('test_id', $this->test->id)->where('user_id', auth()->id())->firstOrFail();
    }

    public function prevSection(): void
    {
        if ($this->prevIsDisabled()) {
            $this->message(__('Önceki alan bulunmuyor, işlem tamamlanamamadı!'))->error();

            return;
        }

        $activeIndex = array_flip($this->sectionNavigation)[$this->active['index']] ?? 0;
        $prev = explode(':', $this->sectionNavigation[$activeIndex - 1]);

        $this->setActive($prev[0] ?? 0, $prev[1] ?? 0);
    }

    public function prevIsDisabled(): bool
    {
        $activeIndex = array_flip($this->sectionNavigation)[$this->active['index']] ?? 0;

        return empty($this->sectionNavigation[$activeIndex - 1]);
    }

    public function nextSection(): void
    {
        if ($this->nextIsDisabled()) {
            $this->message(__('Sonraki alan bulunmuyor, işlem tamamlanamamadı!'))->error();

            return;
        }

        $activeIndex = array_flip($this->sectionNavigation)[$this->active['index']] ?? 0;
        $prev = explode(':', $this->sectionNavigation[$activeIndex + 1]);

        $this->setActive($prev[0] ?? 0, $prev[1] ?? 0);
    }

    public function nextIsDisabled(): bool
    {
        $activeIndex = array_flip($this->sectionNavigation)[$this->active['index']] ?? 0;

        return empty($this->sectionNavigation[$activeIndex + 1]);
    }

    public function saveIsDisabled(): bool
    {
        return $this->finished;
    }

    public function saveAndFinish(): bool
    {
        $result = $this->currentResult();
        if ($result->expires_at->isFuture()) {
            return false;
        }

        $this->testResult = CompleteTestResult::run($result);

        return $this->finished = true;
    }

    public function solvedExams()
    {
        if ($this->finished) {
            return redirect()->route('frontend.solved.exams.detail', $this->test->code);
        }

        $this->message(__('Sınav sonuçları görüntülenemiyor, işlem tamamlanamadı'))->error();

        return false;
    }

    private function startedExams(): void
    {
        $this->testResult = StartTestResult::run($this->test);
        $this->finished = YesNoEnum::YES->is($this->testResult->completed);
        $this->initializeExpiration();
    }

    public function save()
    {
        $result = $this->currentResult();
        if (YesNoEnum::YES->is($result->completed)) {
            $this->finished = true;
            $this->message(__('Sınav daha önce tamamlandı, tekrar yanıtlar kayıt edilemez!'))->error();

            return false;
        }

        $this->testResult = CompleteTestResult::run($result);

        $this->finished = true;
        $this->message(__('Sınav başarıyla tamamlandı ve yanıtlar kayıt edildi!'))->success();

        return true;
    }

    public function render()
    {
        $this->sections->loadMissing('parents.question');

        return view('livewire.frontend.exams.solve-form');
    }
}
