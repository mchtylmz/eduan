<?php

namespace App\Livewire\Tests;

use App\Actions\Tests\CreateOrUpdateTestSectionsAction;
use App\Enums\TestSectionTypeEnum;
use App\Models\Question;
use App\Models\Test;
use App\Traits\CustomLivewireAlert;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class SectionForm extends Component
{
    use CustomLivewireAlert, WithFileUploads;

    public Test $test;

    public array $sections = [];

    protected $listeners = [
        'selectQuestion' => 'selectQuestion',
        'selectPdf' => 'selectPdf',
        'selectContent' => 'selectContent',
    ];

    public function mount(Test $test): void
    {
        $this->test = $test;

        $this->sections = $test->sections()
            ->parentIsZero()
            ->orderBy('order')
            ->get()
            ->map(function ($section) {
                return [
                    'id' => $section->id,
                    'test_id' => $section->test_id,
                    'parent_id' => 0,
                    'type' => $section->type,
                    'name' => $section->name,
                    'order' => $section->order,
                    'show' => false,
                    'fields' => [],
                ];
            })->toArray();

        foreach ($this->sections as $index => $section) {
            if (!empty($section['id'])) {
                $this->sections[$index]['fields'] = $test->sections()
                    ->parent($section['id'])
                    ->orderBy('order')
                    ->get()
                    ->map(function ($section) {
                        $item = [
                            'id' => $section->id,
                            'test_id' => $section->test_id,
                            'parent_id' => $section->parent_id,
                            'type' => $section->type,
                            'name' => $section->name,
                            'order' => $section->order,
                        ];

                        if (TestSectionTypeEnum::PDF->is($section->type)) {
                            $item['meta_file'] = $section->getMeta('meta_file', false);
                        }

                        if (TestSectionTypeEnum::CONTENT->is($section->type)) {
                            $item['content'] = $section->getMeta('content', '');
                            $item['meta_content'] = $section->getMeta('meta_content', '');
                        }

                        if (TestSectionTypeEnum::QUESTION->is($section->type)) {
                            $item['questionId'] = $section->getMeta('questionId', 0);
                            $item['questionParentId'] = $section->getMeta('questionParentId', 0);
                            $item['question'] = Question::find($item['questionId']);
                        }

                        return $item;
                    })
                    ->toArray();
            }
        }
    }

    public function addSection(): void
    {
        $this->sections[] = [
            'id' => 0,
            'test_id' => $this->test->id,
            'parent_id' => 0,
            'type' => TestSectionTypeEnum::TOPIC,
            'name' => '',
            'order' => count($this->sections) + 1,
            'show' => true,
            'fields' => [],
        ];

        $this->message(__('Bölüm eklendi'))->success();

        $this->dispatch('scrollToEndContent');
    }

    public function deleteSection(int $index): void
    {
        unset($this->sections[$index]);

        $this->message(__('Bölüm sınavdan çıkarıldı!'))->success();
    }

    public function addSectionField(int $index, string $type): bool
    {
        $testType = match ($type) {
            'content' => TestSectionTypeEnum::CONTENT,
            'pdf' => TestSectionTypeEnum::PDF,
            'question' => TestSectionTypeEnum::QUESTION,
            default => null,
        };

        if (!$testType) {
            $this->message(__('Bölüm için alan eklenemez!'))->error();
            return false;
        }

        $this->sections[$index]['fields'][] = [
            'id' => 0,
            'test_id' => $this->test->id,
            'parent_id' => $index,
            'type' => $testType,
            'name' => TestSectionTypeEnum::QUESTION->is($testType) ? __('Soru') : '',
            'order' => count($this->sections[$index]['fields']) + 1,
            // 'fields' => [],
        ];

        $this->message(__('Bölüm için alan eklendi'))->success();
        return true;
    }

    public function deleteSectionField(int $sectionIndex, int $fieldIndex): void
    {
        unset($this->sections[$sectionIndex]['fields'][$fieldIndex]);

        $this->message(__('İlgili alan bölümden çıkarıldı!'))->success();
    }

    public function toggleSection(int $index): void
    {
        $this->sections[$index]['show'] = !$this->sections[$index]['show'];
    }

    public function selectQuestion(int $sectionIndex, int $fieldIndex, int $questionId): void
    {
        $this->sections[$sectionIndex]['fields'][$fieldIndex]['questionId'] = $questionId;
        $this->sections[$sectionIndex]['fields'][$fieldIndex]['question'] = Question::find($questionId);
    }

    public function showSelectQuestionModal(int $sectionIndex, int $fieldIndex): void
    {
        $this->dispatch(
            'showModal',
            component: 'tests.select-question-modal-form',
            data: [
                'title' => __('Alan için Soru Seç'),
                'sectionIndex' => $sectionIndex,
                'fieldIndex' => $fieldIndex,
            ]
        );
    }

    public function selectPdf(int $sectionIndex, int $fieldIndex, string $filename): void
    {
        $this->sections[$sectionIndex]['fields'][$fieldIndex]['meta_file'] = $filename;
    }

    public function showSelectPdfModal(int $sectionIndex, int $fieldIndex): void
    {
        $this->dispatch(
            'showModal',
            component: 'tests.select-pdf-modal-form',
            data: [
                'title' => __('Alan için PDF Yükle'),
                'sectionIndex' => $sectionIndex,
                'fieldIndex' => $fieldIndex,
            ]
        );
    }

    public function selectContent(int $sectionIndex, int $fieldIndex, string $content): void
    {
        $this->sections[$sectionIndex]['fields'][$fieldIndex]['content'] = $content;
        $this->sections[$sectionIndex]['fields'][$fieldIndex]['meta_content'] = __(':count karakter içerik girildi', [
            'count' => strlen(strip_tags($content)),
        ]);
    }

    public function showSelectContentModal(int $sectionIndex, int $fieldIndex): void
    {
        $this->dispatch(
            'showModal',
            component: 'tests.select-content-modal-form',
            data: [
                'title' => __('Alan için İçerik Ekle'),
                'sectionIndex' => $sectionIndex,
                'fieldIndex' => $fieldIndex,
                'content' => $this->sections[$sectionIndex]['fields'][$fieldIndex]['content'] ?? ''
            ]
        );
    }

    public function updateSectionsOrder($sorts): void
    {
        foreach ($sorts as $sort) {
            $order = $sort['order'];
            $value = $sort['value'];

            $this->sections[$value]['order'] = $order;
        }
    }

    public function updateSectionFieldsOrder($sorts): void
    {
        foreach ($sorts as $sort) {
            $order = $sort['order'];
            $value = $sort['value'];

            foreach ($sort['items'] as $item) {
                $itemOrder = $item['order'];
                $itemValue = $item['value'];

                $this->sections[$value]['fields'][$itemValue]['order'] = $itemOrder;
            }
        }
    }

    public function sections(): array
    {
        return collect($this->sections)->sortBy('order')->toArray();
    }

    public function rules(): array
    {
        return [
            'sections.*.test_id' => 'required|exists:tests,id',
            'sections.*.name' => 'required',
            'sections.*.fields' => 'required|array|min:1',
            'sections.*.fields.*.name' => 'required',
            'sections.*.fields.*.type' => 'required'
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'sections.*.test_id' => 'Sınav',
            'sections.*.name' => 'Bölüm adı',
            'sections.*.fields' => 'Alanlar',
            'sections.*.fields.*.name' => 'Alan adı',
            'sections.*.fields.*.type' => 'Alan türü'
        ];
    }

    public function save()
    {
        $this->validate();

        if (request()->user()->cannot('tests:update')) {
            $this->message(__('Yetkiniz bulunmuyor, bilgi kayıt edilemez!'))->error();
            return false;
        }

        CreateOrUpdateTestSectionsAction::run(
            sections: $this->sections,
            test: $this->test
        );

        resetCache();

        return redirect()->route('admin.tests.index')->with([
            'status' => 'success',
            'message' => __('Sınav bölümleri başarıyla kayıt edildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.tests.section-form');
    }
}
