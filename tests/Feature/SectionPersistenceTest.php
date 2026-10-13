<?php

namespace Tests\Feature;

use App\Actions\Tests\CreateOrUpdateTestSectionsAction;
use App\Enums\TestSectionTypeEnum;
use App\Livewire\Tests\SectionForm;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Test;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SectionPersistenceTest extends TestCase
{
    use DatabaseTransactions;

    private Test $test;

    private array $sections;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Permission::findOrCreate('tests:update', 'web');
        $user->givePermissionTo('tests:update');
        $this->actingAs($user);
        $lesson = Lesson::create(['code' => 'section-lesson', 'name' => ['nl' => 'Math']]);
        $topic = Topic::create(['lesson_id' => $lesson->id, 'code' => 'section-topic', 'title' => ['nl' => 'Math']]);
        $question = Question::create(['lesson_id' => $lesson->id, 'topic_id' => $topic->id, 'code' => 'section-question', 'locale' => 'nl', 'title' => 'Question']);
        $this->test = Test::create(['code' => 'section-persistence', 'name' => 'Sections', 'locale' => 'nl', 'created_by' => $user->id]);
        $this->sections = [['id' => 0, 'test_id' => $this->test->id, 'parent_id' => 0,
            'type' => TestSectionTypeEnum::TOPIC, 'name' => 'Math', 'order' => 1, 'fields' => [
                ['id' => 0, 'type' => TestSectionTypeEnum::QUESTION, 'name' => 'Question', 'order' => 1, 'questionId' => $question->id],
                ['id' => 0, 'type' => TestSectionTypeEnum::CONTENT, 'name' => 'Content', 'order' => 2, 'content' => 'Instructions'],
            ]]];
    }

    private function persist(): void
    {
        $this->assertTrue(CreateOrUpdateTestSectionsAction::run($this->sections, $this->test));
        $parent = $this->test->sections_with_no_parent()->firstOrFail();
        $this->sections[0]['id'] = $parent->id;
        foreach ($parent->parents()->get() as $index => $field) {
            $this->sections[0]['fields'][$index]['id'] = $field->id;
        }
    }

    public function test_removing_parent_cleans_its_metadata_and_links_without_touching_another_exam(): void
    {
        $this->persist();
        $other = Test::create(['code' => 'other-exam', 'name' => 'Other', 'locale' => 'nl', 'created_by' => auth()->id()]);
        $otherSections = $this->sections;
        $otherSections[0]['id'] = 0;
        $otherSections[0]['test_id'] = $other->id;
        foreach ($otherSections[0]['fields'] as &$field) {
            $field['id'] = 0;
        }
        unset($field);
        $this->assertTrue(CreateOrUpdateTestSectionsAction::run($otherSections, $other));
        $removed = $this->test->sections()->get();

        $this->assertTrue(CreateOrUpdateTestSectionsAction::run([], $this->test));
        $this->assertSame(0, $this->test->sections()->count());
        $this->assertSame(0, $this->test->questions()->count());
        foreach ($removed as $section) {
            $section->refreshLoadedMeta();
            $this->assertCount(0, $section->getMetas());
        }
        $this->assertSame(3, $other->sections()->count());
        $this->assertSame(1, $other->questions()->count());
        $this->assertSame('Instructions', $other->sections()->where('name', 'Content')->firstOrFail()->getMeta('content'));
    }

    public function test_removing_fields_preserves_parent_and_cleans_metadata_and_question_links(): void
    {
        $this->persist();
        $questionField = $this->test->sections()->where('name', 'Question')->firstOrFail();
        array_shift($this->sections[0]['fields']);
        $this->assertTrue(CreateOrUpdateTestSectionsAction::run($this->sections, $this->test));
        $this->assertSame(2, $this->test->sections()->count());
        $this->assertSame(0, $this->test->questions()->count());
        $questionField->refreshLoadedMeta();
        $this->assertCount(0, $questionField->getMetas());

        $this->sections[0]['fields'] = [];
        $this->assertTrue(CreateOrUpdateTestSectionsAction::run($this->sections, $this->test));
        $this->assertSame(1, $this->test->sections()->count());
    }

    public function test_a_failed_section_operation_rolls_back_earlier_changes(): void
    {
        $this->persist();
        $this->sections[0]['name'] = 'Must roll back';
        $invalid = $this->sections[0];
        $invalid['id'] = 999999;
        $this->sections[] = $invalid;
        $this->assertFalse(CreateOrUpdateTestSectionsAction::run($this->sections, $this->test));
        $this->assertSame('Math', $this->test->sections_with_no_parent()->firstOrFail()->name);
        $this->assertSame(3, $this->test->sections()->count());
        $this->assertSame(1, $this->test->questions()->count());
    }

    public function test_failed_save_stays_on_the_form_and_reports_an_error(): void
    {
        CreateOrUpdateTestSectionsAction::shouldRun()->once()->andReturn(false);
        Livewire::test(SectionForm::class, ['test' => $this->test])->set('sections', $this->sections)
            ->call('save')->assertHasNoErrors()->assertNoRedirect()
            ->assertSet('alertOptions.text', __('Sınav bölümleri kayıt edilemedi!'));
        $this->assertSame(0, $this->test->sections()->count());
    }

    public function test_save_without_update_permission_does_not_write_sections(): void
    {
        auth()->user()->revokePermissionTo('tests:update');
        Livewire::test(SectionForm::class, ['test' => $this->test])->set('sections', $this->sections)
            ->call('save')->assertNoRedirect();
        $this->assertSame(0, $this->test->sections()->count());
    }
}
