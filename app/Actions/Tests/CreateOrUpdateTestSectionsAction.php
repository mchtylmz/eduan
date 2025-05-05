<?php

namespace App\Actions\Tests;

use App\Enums\TestSectionTypeEnum;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestsQuestion;
use App\Models\TestsSection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateOrUpdateTestSectionsAction
{
    use AsAction;

    /**
     * @throws \Exception
     */
    public function handle(array $sections, Test $test): bool
    {
        DB::beginTransaction();

        try {
            $incomingSectionIds = [];
            $existingSectionIds = $test->sections()
                ->parentIsZero()
                ->pluck('id')
                ->toArray();

            collect($sections)
                ->sortBy('order')
                ->each(function ($section) use ($test, &$incomingSectionIds) {
                    $testSection = $this->processSection($section, $test);
                    if ($testSection) {
                        $incomingSectionIds[] = $testSection->id;
                    }
                });

            $sectionsToDelete = array_diff($existingSectionIds, $incomingSectionIds);
            if (!empty($sectionsToDelete)) {
                $test->sections()
                    ->parentIsZero()
                    ->whereIn('id', $sectionsToDelete)
                    ->delete();
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            return false;
        }
    }

    private function processSection(array $section, Test $test): ?TestsSection
    {
        $sectionData = $this->prepareSectionData($section);

        if (empty($section['id'])) {
            $testSection = $test->sections()->create($sectionData);
        } else {
            $testSection = $test->sections()->find($section['id']);
            $testSection?->update($sectionData);
        }

        if (!empty($section['fields']) && is_array($section['fields'])) {
            $this->createOrUpdateFields($section['fields'], $testSection);
        }

        return $testSection ?? null;
    }

    private function prepareSectionData(array $section): array
    {
        return [
            'id' => (int) $section['id'],
            'test_id' => (int) $section['test_id'],
            'parent_id' => (int) $section['parent_id'],
            'type' => $section['type'],
            'name' => $section['name'],
            'order' => $section['order'],
        ];
    }

    //// FIELDS
    public function createOrUpdateFields(array $fields, TestsSection $testsSection): void
    {
        $incomingFieldsIds = [];
        $existingFieldsIds = TestsSection::where('test_id', $testsSection->test_id)
            ->parent($testsSection->id)
            ->pluck('id')
            ->toArray();

        collect($fields)
            ->sortBy('order')
            ->each(function ($field) use ($testsSection, &$incomingFieldsIds) {
                $testField = $this->processField($field, $testsSection);
                if ($testField) {
                    $incomingFieldsIds[] = $testField->id;

                    $this->createOrUpdateFieldQuestion($field, $testField);
                }
            });

        $fieldsToDelete = array_diff($existingFieldsIds, $incomingFieldsIds);
        if (!empty($fieldsToDelete)) {
            TestsSection::where('test_id', $testsSection->test_id)
                ->parent($testsSection->id)
                ->whereIn('id', $fieldsToDelete)
                ->delete();

            TestsQuestion::where('test_id', $testsSection->test_id)
                ->whereIn('section_id', $fieldsToDelete)
                ->delete();
        }
    }

    private function processField(array $field, TestsSection $testsSection): ?TestsSection
    {
        $fieldData = $this->prepareFieldData($field, $testsSection);

        if (empty($fieldData['id'])) {
            $testField = TestsSection::create($fieldData);
        } else {
            $testField = TestsSection::find($fieldData['id']);
            $testField?->update($fieldData);
        }

        foreach (['meta_file', 'content', 'meta_content', 'questionId', 'questionParentId'] as $key) {
            if (isset($field[$key]))
                $testField->setMeta($key, $field[$key]);
        }

        return $testField ?? null;
    }

    private function prepareFieldData(array $field, TestsSection $testsSection): array
    {
        return [
            'id' => (int) $field['id'],
            'test_id' => $testsSection->test_id,
            'parent_id' => $testsSection->id,
            'type' => $field['type'],
            'name' => $field['name'],
            'order' => $field['order'],
        ];
    }

    private function createOrUpdateFieldQuestion(array $field, TestsSection $testsField): void
    {
        if (
            TestSectionTypeEnum::QUESTION->is($testsField->type) &&
            $question = Question::find($field['questionId'] ?? 0)
        ) {
            TestsQuestion::updateOrCreate(
                [
                    'test_id' => $testsField->test_id,
                    'section_id' => $testsField->id,
                ],
                [
                    'question_id' => $question->id,
                    'lesson_id' => $question->lesson_id,
                    'topic_id' => $question->topic_id,
                    'order' => $testsField->order,
                ]
            );
        }
    }
}
