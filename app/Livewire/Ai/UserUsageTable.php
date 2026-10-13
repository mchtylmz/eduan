<?php

namespace App\Livewire\Ai;

use App\Helpers\LearningTable;
use App\Helpers\TableCache;
use App\Models\AnswerAI;
use App\Models\User;
use App\Models\UserAiUsage;
use App\Traits\CustomLivewireAlert;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UserUsageTable extends Component
{
    use CustomLivewireAlert;

    #[Locked]
    public int $answerAiId = 0;

    public function mount(int $answerAiId = 0): void
    {
        LearningTable::access('ai');
        $this->answerAiId = $answerAiId;
    }

    public function render()
    {
        LearningTable::access('ai');
        if ($this->answerAiId) {
            AnswerAI::findOrFail($this->answerAiId);
        }
        $students = LearningTable::studentIds();
        $scope = hash('sha256', json_encode([$students, $this->answerAiId]));

        return view('livewire.backend.ai.usages-server-table', ['users' => TableCache::options('ai-usages-users-'.$scope,
            fn () => User::when($students !== null, fn ($q) => $q->whereIn('id', $students))->get(['id', 'name', 'surname', 'email'])
                ->mapWithKeys(fn ($u) => [$u->id => sprintf('(%s) %s', $u->email, $u->display_name)])->all())]);
    }

    public function delete(int $id): bool
    {
        LearningTable::access('ai');
        abort_unless(auth()->user()->can('ai:delete'), 403);
        $students = LearningTable::studentIds();
        $record = UserAiUsage::when($this->answerAiId, fn ($q) => $q->where('answer_ai_id', $this->answerAiId))
            ->when($students !== null, fn ($q) => $q->whereIn('user_id', $students))->findOrFail($id);
        $deleted = $record->delete();
        $this->dispatch('refresh-server-table', table: 'ai-usages');

        return $deleted;
    }
}
