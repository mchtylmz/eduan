<?php

namespace App\Livewire\Topics;

use App\Actions\Lessons\CreateOrUpdateLessonAction;
use App\Actions\Topics\CreateOrUpdateTopicAction;
use App\Enums\StatusEnum;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Lazy(isolate: true)]
class TopicForm extends Component
{
    public Topic $topic;

    public array $title = [];
    public array $description = [];
    public int $lesson_id;
    public string $code;
    public int $sort = 1;
    public StatusEnum $status = StatusEnum::ACTIVE;

    public string $permission = 'topics:add';

    public function mount(int $topicId = 0): void
    {
        $this->code = Str::slug(Str::random(4));

        if ($topicId && $this->topic = Topic::find($topicId)) {
            $this->lesson_id = $this->topic->lesson_id;
            $this->code = $this->topic->code;
            $this->sort = $this->topic->sort;
            $this->status = $this->topic->status;
            $this->title = $this->topic->getTranslations('title');
            $this->description = $this->topic->getTranslations('description');

            $this->permission = 'topics:update';
        }
    }

    #[Computed(cache: true)]
    public function lessons()
    {
        return Lesson::active()->orderBy('sort')->get();
    }

    public function rules(): array
    {
        return [
            'lesson_id' => 'required|integer|exists:lessons,id',
            'code' => ['required', Rule::unique('topics', 'code')->ignore($this->topic->id ?? 0)],
            'sort' => 'required|integer',
            'title' => 'required|array|min:1',
            'status' => [
                'required',
                new Enum(StatusEnum::class)
            ]
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'lesson_id' => __('Ders'),
            'code' => __('Kodu'),
            'sort' => __('Sıra'),
            'title' => __('Konu Başlığı'),
            'status' => __('Durum'),
        ];
    }

    public function save()
    {
        $this->validate();

        CreateOrUpdateTopicAction::run(
            data: [
                'lesson_id' => $this->lesson_id,
                'code' => Str::slug($this->code),
                'status' => $this->status,
                'sort' => $this->sort,
                'title' => $this->title,
                'description' => $this->description,
            ],
            topic: !empty($this->topic) && $this->topic->exists ? $this->topic : null
        );

        return redirect()->route('admin.topics.index')->with([
            'status' => 'success',
            'message' => __('Konu kayıt edildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.topics.topic-form');
    }
}
