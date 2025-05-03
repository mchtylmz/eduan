<?php

namespace App\Livewire\Frontend\Exams;

use App\Models\Test;
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
    public TestsSection $content;
    public Collection $sections;

    public array $active = [];
    public array $sectionHistory = [];
    public array $sectionNavigation = [];
    public array $results = [];
    /*
     results = [
        section_id => [
            parent_id => [
                question_id => answer_id
            ]
        ]
    ]
     * */

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
        $this->sections = $test->sections()->parentIsZero()->get();

        $this->totalQuestionsCount = $test->questions()->count();
        $this->setActive(0, $this->sections?->first()->parents->first()?->id);

        $this->expirationTime = now()->addSeconds($this->test->duration)->format('Y-m-d H:i:s');
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

    #[Computed]
    public function resultQuestionsCount(): int
    {
        $count = 0;
        foreach ($this->results as $section) {
            $count += count($section);
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
        return $this->resultQuestionsCount() != $this->totalQuestionsCount;
    }

    public function saveAndFinish(): bool
    {
        return $this->finished = true;
    }

    public function refreshIfFinished()
    {
        if ($this->finished) {
            return redirect()->route('frontend.exam.detail', $this->test->code);
        }

        return false;
    }

    public function save()
    {
        if (request()->user()->cannot('tests:solve')) {
            $this->message(__('Sınav tamamlama yetkiniz bulunmuyor!'))->error();
            return false;
        }

        if (!$this->finished) {
            $this->message(__('Sınav daha önce tamamlandı, tekrar yanıtlar kayıt edilemez!'))->error();
            return false;
        }

        if ($this->resultQuestionsCount() != $this->totalQuestionsCount || empty($this->results)) {
            $this->message(__('Yanıtlanmayan sorular bulunuyor!'))->error();
            return false;
        }

    }

    public function render()
    {
        return view('livewire.frontend.exams.solve-form');
    }
}
