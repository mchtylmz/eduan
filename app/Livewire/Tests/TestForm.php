<?php

namespace App\Livewire\Tests;

use App\Actions\Exams\CreateOrUpdateExamAction;
use App\Actions\Tests\CreateOrUpdateAction;
use App\Enums\StatusEnum;
use App\Enums\VisibilityEnum;
use App\Models\Test;
use App\Traits\CustomLivewireAlert;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Component;
use Livewire\WithPagination;

class TestForm extends Component
{
    use WithPagination, CustomLivewireAlert;

    protected string $paginationTheme = 'bootstrap';

    public ?Test $test = null;

    public string $locale;
    public string $code;
    public string $name;
    public string $content = '';
    public int $duration = 300;
    public int $passingScore = 60;
    public int $correctPoint = 3;
    public int $incorrectPoint = -1;
    public StatusEnum $status = StatusEnum::ACTIVE;

    public string $permission = 'tests:add';

    public function mount(?Test $test = null): void
    {
        $this->test = $test;

        $this->code = sprintf('sinav-%s', rand(11, 999999));
        $this->locale = settings()->testlanguageCode ?? app()->getLocale();
        $this->duration = settings()->testTime ?? 300;
        $this->correctPoint = intval(settings()->examCorrectPoint ?: 3);
        $this->incorrectPoint = intval(settings()->examIncorrectPoint ?: -1);
        $this->passingScore = intval(settings()->examPassingScore ?: 60);

        if ($this->test && $this->test->exists) {
            $this->initializeExistingTest();
        }
    }

    public function initializeExistingTest()
    {
        $this->name = $this->test->name;
        $this->locale = $this->test->locale;
        $this->code = $this->test->code;
        $this->content = $this->test->content;
        $this->duration = $this->test->duration;
        $this->status = $this->test->status;
        $this->correctPoint = intval($this->test->correct_point);
        $this->incorrectPoint = intval($this->test->incorrect_point);
        $this->passingScore = intval($this->test->passing_score);

        $this->permission = 'exams:update';
    }

    public function rules(): array
    {
        return [
            'locale' => 'required|string|exists:languages,code',
            'code' => [
                'required', 'string', 'min:2',
                Rule::unique('tests', 'code')->ignore($this->test->id ?? 0)
            ],
            'name' => 'required',
            'duration' => 'required|integer|min:30',
            'passingScore' => 'required|integer|min:1',
            'correctPoint' => 'required|integer',
            'incorrectPoint' => 'required|integer',
            'status' => ['required', new Enum(StatusEnum::class)],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'locale' => __('Dil'),
            'code' => __('Sınav kodu'),
            'name' => __('Sınav adı'),
            'duration' => __('Süre (dk)'),
            'passingScore' => __('Geçme Puanı'),
            'correctPoint' => __('Doğru Yanıt Puanı'),
            'incorrectPoint' => __('Yanlış Yanıt Puanı'),
            'status' => __('Durum'),
        ];
    }

    public function save()
    {
        $this->validate();

        $test = CreateOrUpdateAction::run(
            data: [
                'locale' => $this->locale,
                'code' => $this->code,
                'name' => $this->name,
                'content' => $this->content,
                'duration' => $this->duration,
                'status' => $this->status,
                'passing_score' => $this->passingScore,
                'correct_point' => $this->correctPoint,
                'incorrect_point' => $this->incorrectPoint,
            ],
            test: !empty($this->test) && $this->test->exists ? $this->test : null
        );

        return redirect()
            ->route('admin.tests.index')
            ->with([
                'status' => 'success',
                'message' => __('Sınav bilgileri kayıt edildi')
            ]);
    }

    public function render()
    {
        return view('livewire.backend.tests.test-form');
    }
}
