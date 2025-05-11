<?php

namespace App\Livewire\Frontend\Exams;

use App\Actions\Tests\CreateOrUpdateTestResult;
use App\Actions\Tests\CreateOrUpdateTestResultDetail;
use App\Enums\TestSectionTypeEnum;
use App\Enums\YesNoEnum;
use App\Models\Test;
use App\Models\TestsResult;
use App\Models\TestsSection;
use App\Traits\CustomLivewireAlert;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class SolveForm extends Component
{
    use WithPagination, WithoutUrlPagination, CustomLivewireAlert;

    public Test $test;
    public TestsResult $testResult;
    public TestsSection $content;
    public Collection $sections;

    public array $active = [];
    public array $sectionHistory = [];
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
    public int $totalQuestionsCount = 0;
    public string $expirationTime = '';
    public bool $finished = false;

    protected $listeners = [
        'saveAndFinish' => 'saveAndFinish',
    ];

    public function mount(Test $test): void
    {
        $this->test = $test;
        if (!$this->test->sections_with_no_parent()->count() || !$this->test->questions()->count()) {
            redirect()->route('frontend.exams');
            return;
        }

        $this->sections = $test->sections()->parentIsZero()->get();

        $this->totalQuestionsCount = $test->questions()->count();
        $this->setActive(0, $this->sections?->first()->parents->first()?->id);

        $this->initializeResultsArray();
        $this->initializeExpiration();
    }

    public function initializeResultsArray(): void
    {
        collect($this->sections)->each(function ($section, $sectionIndex) {
            $parents = $section->parents()->where('type', TestSectionTypeEnum::QUESTION)->get();

            collect($parents)->each(function ($parent) use ($sectionIndex) {
                $this->results[$sectionIndex][$parent->id] = [
                    $parent->question->question_id ?? 0 => 0
                ];
            });
        });
    }

    public function initializeExpiration(): void
    {
        $this->expirationTime = now(settings()->timezone ?? config('app.timezone'))
            ->addSeconds($this->test->duration)->format('Y-m-d H:i:s');

        if ($expirationTime = request()->session()->get('expirationTime_' . $this->test->id, false)) {
            // $this->expirationTime = $expirationTime;
            // geçici kapalı
        } else {
            request()->session()->put('expirationTime_' . $this->test->id, $this->expirationTime);
        }
    }

    private function forgetExpiration(): void
    {
        request()->session()->forget('expirationTime_' . $this->test->id);
    }

    public function setActive(int $sectionKey, int $parentId): void
    {
        $this->active = [
            'index' => $this->sectionNavigationValue($sectionKey, $parentId),
            'section' => $sectionKey,
            'parent' => $parentId
        ];

        $this->sectionHistory[] = $parentId;
    }

    private function sectionNavigationValue(int $sectionKey, int $parentId): string
    {
        return $sectionKey . ':' . $parentId;
    }

    public function addSectionNavigation(int $sectionKey, int $parentId): void
    {
        $value = $this->sectionNavigationValue($sectionKey, $parentId);
        if (!in_array($value, array_values($this->sectionNavigation))) {
            $this->sectionNavigation[] = $value;
        }
    }

    #[Computed]
    public function getContent()
    {
        if ($this->finished) {
            $this->dispatch('showFinishedModal');
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
                if (array_values($question)[0])
                    $count++;
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
        return false;
        // return $this->resultQuestionsCount() != $this->totalQuestionsCount;
    }

    public function saveAndFinish(): bool
    {
        $this->forgetExpiration();

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

    public function startedExams(): bool
    {
        if ($this->finished) {
            return false;
        }

        $this->testResult = CreateOrUpdateTestResult::run(
            attributes: [
                'user_id' => auth()->id(),
                'test_id' => $this->test->id,
                'completed' => YesNoEnum::NO,
            ],
            values: [
                'question_count' => $this->totalQuestionsCount,
                'passing_score' => $this->test->passing_score ?? 60,
                'expires_at' => $this->expirationTime,
            ]
        );
        return true;
    }

    public function save()
    {
        if (request()->user()->cannot('tests:solve')) {
            $this->message(__('Sınav tamamlama yetkiniz bulunmuyor!'))->error();
            return false;
        }

        if ($this->finished) {
            $this->message(__('Sınav daha önce tamamlandı, tekrar yanıtlar kayıt edilemez!'))->error();
            return false;
        }

        /*
        if ($this->resultQuestionsCount() != $this->totalQuestionsCount || empty($this->results)) {
            $this->message(__('Yanıtlanmayan sorular bulunuyor!'))->error();
            return false;
        }
        */

        if (empty($this->testResult)) {
            $this->startedExams();
        }

        $this->testResult = CreateOrUpdateTestResultDetail::run(
            results: $this->results,
            testResult: $this->testResult
        );

        $this->finished = true;
        $this->forgetExpiration();

        $this->message(__('Sınav başarıyla tamamlandı ve yanıtlar kayıt edildi!'))->success();
        return true;
    }

    public function render()
    {
        return view('livewire.frontend.exams.solve-form');
    }
}
