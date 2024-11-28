<?php

namespace App\Livewire\Lessons;

use App\Actions\Lessons\CreateOrUpdateLessonAction;
use App\Enums\StatusEnum;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

class LessonForm extends Component
{
    public Lesson $lesson;

    public array $name = [];
    public array $description = [];
    public string $code;
    public int $sort = 1;
    public StatusEnum $status = StatusEnum::ACTIVE;

    public string $permission = 'lessons:add';

    public function mount(int $lessonId = 0): void
    {
        $this->code = Str::slug(Str::random(4));

        if ($lessonId && $this->lesson = Lesson::find($lessonId)) {
            $this->code = $this->lesson->code;
            $this->sort = $this->lesson->sort;
            $this->status = $this->lesson->status;

            $this->name = $this->lesson->getTranslations('name');
            $this->description = $this->lesson->getTranslations('description');

            $this->permission = 'lessons:update';
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', Rule::unique('lessons', 'code')->ignore($this->lesson->id ?? 0)],
            'sort' => 'required|integer',
            'name' => 'required|array|min:1',
            'status' => [
                'required',
                new Enum(StatusEnum::class)
            ]
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'code' => __('Kodu'),
            'sort' => __('Sıra'),
            'name' => __('Ders Adı'),
            'status' => __('Durum'),
        ];
    }

    public function save()
    {
        $this->validate();

        CreateOrUpdateLessonAction::run(
            data: [
                'code' => Str::slug($this->code),
                'status' => $this->status,
                'sort' => $this->sort,
                'name' => $this->name,
                'description' => $this->description,
            ],
            lesson: !empty($this->lesson) && $this->lesson->exists ? $this->lesson : null
        );

        return redirect()->route('admin.lessons.index')->with([
            'status' => 'success',
            'message' => __('Ders kayıt edildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.lessons.lesson-form');
    }
}
