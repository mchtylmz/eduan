<?php

namespace App\Livewire\Blogs;

use App\Helpers\TableCache;
use App\Models\Blog;
use App\Models\Language;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class BlogsTable extends Component
{
    use CustomLivewireAlert, ServerTableActions;

    protected string $resource = 'blogs';

    protected string $tableName = 'blogs';

    protected function builder(): Builder
    {
        return Blog::query()->with('language');
    }

    public function render()
    {
        $record = $this->selectedRecord();

        return view('livewire.backend.blogs.server-table', [
            'languages' => TableCache::options('content-languages', fn () => Language::orderBy('id')->pluck('name', 'code')->all()),
            'details' => $record ? [__('Dil') => $record->language?->name ?? $record->locale, __('Başlık') => $record->title, __('Görüntüleme') => $record->hits, __('Durum') => $record->status->name(), __('Yayın Tarihi') => dateFormat($record->published_at, 'd M Y, H:i')] : [],
            'editUrl' => $record && auth()->user()->can('blogs:update') ? route('admin.blogs.edit', $record) : null,
        ]);
    }

    public function delete(Blog $blog)
    {
        $this->requireTableAccess('delete');
        if (auth()->user()->cannot('blogs:delete')) {
            $this->message(__('Blog silinemedi!'))->error();

            return false;
        }

        $this->message(__('Blog başarıyla silindi!'))->success();

        $deleted = $blog->delete();
        $this->dispatch('refresh-server-table', table: 'blogs');

        return $deleted;
    }
}
