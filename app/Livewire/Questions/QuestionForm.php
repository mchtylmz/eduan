<?php

namespace App\Livewire\Questions;

use App\Actions\Files\UploadFileAction;
use App\Actions\Questions\CreateQuestionAction;
use App\Actions\Questions\UpdateQuestionAction;
use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Models\Answer;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class QuestionForm extends Component
{
    use WithFileUploads;

    public ?Question $question;
    public Collection $topics;

    public int $lesson_id;
    public int $topic_id;
    public string $locale;
    public string $title = '';
    public string $code = '';
    public int $sort = 1;
    public int $time = 300;
    public StatusEnum $status = StatusEnum::ACTIVE;

    public $attachment;
    public $solution;
    public array $answers = [];

    public string $permission = 'questions:add';

    public function mount(int $questionId = 0): void
    {
        if ($questionId && $this->question = Question::find($questionId)) {
            $this->lesson_id = $this->question->lesson_id;

            $this->topic_id = $this->question->topic_id;
            $this->topics = Topic::where('lesson_id', $this->lesson_id)
                ->orWhere('id', $this->topic_id)
                ->orderBy('sort')
                ->get();

            $this->locale = $this->question->locale;
            $this->title = $this->question->title;
            $this->code = $this->question->code;
            $this->sort = $this->question->sort;
            $this->time = $this->question->time;
            $this->status = $this->question->status;

            $this->answers = $this->question->answers->map(function ($answer) {
                return $this->formatAnswer($answer);
            })->toArray();

            $this->permission = 'questions:update';
        } else {
            $this->code = Str::random(5);
            $this->locale = settings()->examlanguageCode ?? app()->getLocale();
            $this->time = settings()->examTime ?? 300;

            $defaultQuestionCount = settings()->examAnswerCount ?? 4;
            for ($i = 1; $i <= $defaultQuestionCount; $i++) {
                $this->answerAdd();
            }

        }
    }

    #[Computed(cache: true)]
    public function lessons()
    {
        return Lesson::withCount('topics')->active()->orderBy('sort')->get();
    }

    public function updatedLessonId($value)
    {
        $this->topic_id = 0;
        return $this->topics = Topic::active()->where('lesson_id', $value)->orderBy('sort')->get();
    }

    public function updatedTopicId($value): void
    {
        if ($this->sort <= 1) {
            $this->sort = Question::where('lesson_id', $this->lesson_id)->where('topic_id', $this->topic_id)->count() + 1;
        }

        if (empty($this->question)) {
            $this->code = sprintf(
                '%s%s%d',
                Lesson::find($this->lesson_id)->code ?? Str::random(3),
                Topic::find($this->topic_id)->code ?? Str::random(3),
                $this->sort
            );
        }
    }

    protected function formatAnswer(Answer $answer): array
    {
        return [
            'id' => $answer->id,
            'title' => $answer->title,
            'correct' => $answer->correct,
        ];
    }

    public function answerAdd(): void
    {
        $this->answers[] = [
            'title' => letters(count($this->answers ?? [])),
            'correct' => YesNoEnum::NO
        ];
    }

    public function answerRemove(int $index): void
    {
        unset($this->answers[$index]);

        $this->answers = array_values($this->answers);
    }

    public function rules(): array
    {
        return [
            'lesson_id' => 'required|integer|exists:lessons,id',
            'topic_id' => 'required|integer|exists:topics,id',
            'locale' => 'required|string|exists:languages,code',
            'title' => 'nullable|string',
            'code' => ['required', Rule::unique('questions', 'code')->ignore($this->question->id ?? 0)],
            'time' => 'required|integer',
            'sort' => 'required|integer',
            'attachment' => [
                Rule::when(
                    !empty($this->question) && !$this->question->exists,
                    'required',
                    'nullable'
                ),
                'image'
            ],
            'solution' => [
                Rule::when(
                    !empty($this->question) && !$this->question->exists,
                    'required',
                    'nullable'
                ),
                'image'
            ],
            'answers' => 'required|array',
            'answers.*.title' => ['required'],
            'answers.*.correct' => [new Enum(YesNoEnum::class)],
            'status' => [
                'required',
                new Enum(StatusEnum::class)
            ]
        ];
    }

    public function boot(): void
    {
        $this->withValidator(function ($validator) {
            $validator->after(function ($validator) {
                $correctCount = collect($this->answers)
                    ->where('correct', YesNoEnum::YES)
                    ->count();

                if ($correctCount > 1) {
                    $validator->errors()->add('answers', __('Sadece bir cevap "Doğru" olarak işaretlenebilir.'));
                }
                elseif ($correctCount == 0) {
                    $validator->errors()->add('answers', __('En az bir cevap "Doğru" olarak işaretlenebilir.'));
                }
            });
        });
    }

    public function validationAttributes(): array
    {
        return [
            'lesson_id' => __('Ders'),
            'topic_id' => __('Konu'),
            'locale' => __('Dil'),
            'title' => __('Soru Metni'),
            'time' => __('Süre'),
            'code' => __('Kodu'),
            'sort' => __('Sıra'),
            'attachment' => __('Soru Resmi'),
            'solution' => __('Soru Çözümü'),
            'answers' => __('Yanıtlar'),
            'answers.*.title' => __('Yanıt'),
            'answers.*.correct' => __('Doğru/Yanlış'),
            'status' => __('Durum'),
        ];
    }

    protected function prepareData(): array
    {
        $data = [
            'lesson_id' => $this->lesson_id,
            'topic_id' => $this->topic_id,
            'title' => $this->title,
            'locale' => $this->locale,
            'code' => $this->code,
            'time' => $this->time,
            'sort' => $this->sort,
            'status' => $this->status,
        ];

        if ($this->attachment instanceof TemporaryUploadedFile) {
            $data['attachment'] = UploadFileAction::run(file: $this->attachment, folder: 'questions');
        }
        if ($this->solution instanceof TemporaryUploadedFile) {
            $data['solution'] = UploadFileAction::run(file: $this->solution, folder: 'questions');
        }

        return $data;
    }

    public function save()
    {
        $this->validate();

        if (!empty($this->question) && $this->question->exists) {
            UpdateQuestionAction::run(
                question: $this->question,
                data: $this->prepareData(),
                answers: $this->answers
            );
        } else {
            CreateQuestionAction::run(
                data: $this->prepareData(),
                answers: $this->answers
            );
        }

        flush();

        return redirect()->route('admin.questions.index')->with([
            'status' => 'success',
            'message' => __('Soru ve yanıtlar kayıt edildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.questions.question-form');
    }
}
