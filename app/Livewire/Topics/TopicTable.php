<?php

namespace App\Livewire\Topics;

use App\Helpers\TableCache;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Topic;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class TopicTable extends Component
{
    use CustomLivewireAlert, ServerTableActions;

    protected string $resource = 'topics';

    protected function builder(): Builder
    {
        return Topic::with('lesson')->withCount('questions');
    }

    public function render()
    {
        $record = $this->selectedRecord();

        return view('livewire.backend.topics.server-table', [
            'languages' => TableCache::options('languages', fn () => Language::orderBy('id')->pluck('name', 'code')->all()),
            'lessons' => TableCache::options('lessons', fn () => Lesson::active()->orderBy('sort')->get()->pluck('name', 'id')->all()),
            'details' => $record ? [__('Ders') => $record->lesson?->name ?? '-', __('Kodu') => $record->code,
                __('Konu') => $record->title, __('Soru Sayısı') => $record->questions_count,
                __('Sıra') => $record->sort, __('Görüntüleme') => $record->hits, __('Durum') => $record->status->name()] : [],
        ]);
    }

    public function delete(Topic $topic)
    {
        $this->requireTableAccess('delete');
        if (auth()->user()->cannot('topics:delete')) {
            $this->message(__('Konu silinemedi!'))->error();

            return false;
        }

        if ($topic->questions()->count()) {
            $this->message(__('Konu silinemez, bağlı soru bulunuyor!'))->error();

            return false;
        }

        $this->message(__('Konu başarıyla silindi!'))->success();

        $deleted = $topic->delete();
        $this->dispatch('refresh-server-table', table: $this->resource);

        return $deleted;
    }
}
