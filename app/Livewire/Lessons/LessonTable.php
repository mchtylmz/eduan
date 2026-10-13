<?php

namespace App\Livewire\Lessons;

use App\Helpers\TableCache;
use App\Models\Language;
use App\Models\Lesson;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class LessonTable extends Component
{
    use CustomLivewireAlert, ServerTableActions;

    protected string $resource = 'lessons';

    protected function builder(): Builder
    {
        return Lesson::withCount(['topics', 'questions']);
    }

    public function render()
    {
        $record = $this->selectedRecord();

        return view('livewire.backend.lessons.server-table', [
            'languages' => TableCache::options('languages', fn () => Language::orderBy('id')->pluck('name', 'code')->all()),
            'details' => $record ? [__('Kodu') => $record->code, __('Ders Adı') => $record->name,
                __('Konu Sayısı') => $record->topics_count, __('Soru Sayısı') => $record->questions_count,
                __('Sıra') => $record->sort, __('Görüntüleme') => $record->hits, __('Durum') => $record->status->name()] : [],
        ]);
    }

    public function delete(Lesson $lesson)
    {
        $this->requireTableAccess('delete');
        if (auth()->user()->cannot('lessons:delete')) {
            $this->message(__('Ders silinemedi!'))->error();

            return false;
        }

        if ($lesson->topics()->count()) {
            $this->message(__('Ders silinemez, bağlı konu bulunuyor!'))->error();

            return false;
        }

        if ($lesson->questions()->count()) {
            $this->message(__('Ders silinemez, bağlı soru bulunuyor!'))->error();

            return false;
        }

        $this->message(__('Ders başarıyla silindi!'))->success();

        $deleted = $lesson->delete();
        $this->dispatch('refresh-server-table', table: $this->resource);

        return $deleted;
    }
}
