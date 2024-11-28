<?php

namespace App\Livewire\Exams;

use App\Actions\Exams\CreateOrUpdateExamAction;
use App\Actions\Exams\UpdateExamQuestionsAction;
use App\Enums\StatusEnum;
use App\Enums\VisibilityEnum;
use App\Enums\YesNoEnum;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Topic;
use App\Traits\CustomLivewireAlert;
use Faker\Core\Number;
use Faker\Provider\tr_TR\PhoneNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

class ExamForm extends Component
{
    use WithPagination, CustomLivewireAlert;

    protected string $paginationTheme = 'bootstrap';

    public ?Exam $exam = null;

    public int $topic_id = 0;
    public int $lesson_id = 0;
    public string $locale;
    public string $code;
    public string $name;
    public string $content = '';
    public VisibilityEnum $visibility = VisibilityEnum::PREMIUM;
    public StatusEnum $status = StatusEnum::ACTIVE;

    public array $examQuestions = [];
    public string $permission = 'exams:add';

    public function mount(?Exam $exam = null): void
    {
        $this->exam = $exam;

        $this->code = sprintf('test-%s', rand(99, 99999));
        $this->locale = settings()->examlanguageCode ?? app()->getLocale();

        if ($this->exam && $this->exam->exists) {
            $this->initializeExistingExam();
        }
    }

    #[Computed(cache: true)]
    public function lessons()
    {
        return Lesson::active()->withCount('topics')->get();
    }

    #[Computed(cache: true)]
    public function topics()
    {
        return Topic::active()->where('lesson_id', $this->lesson_id)->withCount('questions')->get();
    }

    public function updatedLessonId($value): void
    {
        $this->topic_id = 0;
    }

    public function initializeExistingExam()
    {
        $this->name = $this->exam->name;
        $this->locale = $this->exam->locale;
        $this->code = $this->exam->code;
        $this->content = $this->exam->content;
        $this->visibility = $this->exam->visibility;
        $this->lesson_id = $this->exam->lesson_id ?? 0;
        $this->topic_id = $this->exam->topic_id ?? 0;
        $this->status = $this->exam->status;

        $this->examQuestions = $this->exam->questions()->orderBy('order')->get()
            ->map(fn($question, $index) => ['order' => $index + 1, 'question' => $question])
            ->toArray();
        $this->examQuestions = $this->examQuestions()->toArray();

        $this->permission = 'exams:update';
    }

    public function updated($field): void
    {
        if (in_array($field, ['topic_id', 'lesson_id']) && $this->topic_id && $this->lesson_id) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function questions()
    {
        return Question::active()
            ->where('topic_id', $this->topic_id)
            ->where('lesson_id', $this->lesson_id)
            ->latest()
            ->paginate(8);
    }

    #[Computed]
    public function examQuestions(): \Illuminate\Support\Collection
    {
        return collect($this->examQuestions)->keyBy('question.id')->sortBy('order');
    }

    #[Computed]
    public function examQuestionsStatistic(): array
    {
        $questions = $this->examQuestions();

        return [
            'count' => $questions->count(),
            'sumTime' => $questions->sum('question.time'),
            'sumMinute' => intval($questions->sum('question.time') / 60),
        ];
    }

    public function updateExamQuestionsOrder($sorts): void
    {
        foreach ($sorts as $sort) {
            $order = $sort['order'];
            $value = $sort['value'];

            $this->examQuestions[$value]['order'] = $order;
        }
    }

    public function toggleQuestions($questionId): true
    {
        if (!empty($this->examQuestions[$questionId])) {
            unset($this->examQuestions[$questionId]);
        } else {
            $this->examQuestions[$questionId] = [
                'order' => 1,
                'question' => Question::find($questionId)
            ];
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => 'required|string|exists:languages,code',
            'code' => 'required|string|min:2',
            'name' => 'required',
            'visibility' => ['required', new Enum(VisibilityEnum::class)],
            'status' => ['required', new Enum(StatusEnum::class)],
            'examQuestions' => 'required|array',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'locale' => __('Dil'),
            'code' => __('Kodu'),
            'name' => __('Test adı'),
            'visibility' => __('Çözülebilme Durumu'),
            'status' => __('Durum'),
            'examQuestions' => __('Soru seçimi'),
        ];
    }

    public function save()
    {
        $this->validate();

        $exam = CreateOrUpdateExamAction::run(
            data: [
                'locale' => $this->locale,
                'code' => $this->code,
                'name' => $this->name,
                'content' => $this->content,
                'visibility' => $this->visibility,
                'status' => $this->status,
            ],
            exam: !empty($this->exam) && $this->exam->exists ? $this->exam : null
        );

        $this->updateExamRelations($exam);

        return redirect()
            ->route('admin.exams.index')
            ->with([
                'status' => 'success',
                'message' => __('Test bilgileri kayıt edildi')
            ]);
    }

    public function updateExamRelations(Exam $exam): void
    {
        UpdateExamQuestionsAction::run(
            exam: $exam,
            questions: $this->examQuestions()->map(fn($item) => [
                'order' => $item['order'],
                'lesson_id' => $item['question']->lesson_id ?? 0,
                'topic_id' => $item['question']->topic_id ?? 0,
            ])->toArray()
        );
    }

    public function render()
    {
        return view('livewire.backend.exams.exam-form');
    }
}
