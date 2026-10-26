<?php

namespace App\Livewire;

use App\Traits\CustomLivewireAlert;
use Livewire\Component;

class Events extends Component
{
    use CustomLivewireAlert;

    protected $listeners = [
        'runEvent' => 'run',
    ];

    public function run(string $event, mixed $data = null): void
    {
        abort_unless(in_array($event, ['darkMode', 'sidebarMini', 'settingRemove', 'clearCache'], true), 403);
        $this->{$event}($data);
    }

    protected function darkMode(mixed $data): void
    {
        if (auth()->check()) {
            $darkMode = user()->getMeta('darkMode');
            user()->setMeta('darkMode', ! $darkMode ? 1 : 0);
        }
    }

    protected function sidebarMini(mixed $data): void
    {
        if (auth()->check()) {
            $sidebarMini = user()->getMeta('sidebarMini');
            user()->setMeta('sidebarMini', ! $sidebarMini ? 1 : 0);
        }
    }

    protected function settingRemove(mixed $data)
    {
        abort_unless(auth()->user()?->can('dashboard:access') && auth()->user()->can('settings:update'), 403);
        abort_unless(is_array($data) && in_array($data['column'] ?? '', [
            'siteFavicon', 'siteLogo', 'siteLogoWhite', 'coverAuth', 'coverBlog', 'coverContact',
            'coverLessons', 'coverTopics', 'coverTests', 'coverAccount', 'coverFaq', 'coverPage',
        ], true), 422);
        if (empty($data['column'])) {
            $this->message(__('Seçilen alan bulunamadı!'))->success();

            return false;
        }

        settings()->set($data['column'], null);
        settings()->save();

        return redirect()->route('admin.settings.index', ['activeTab' => $data['tab'] ?? 'general'])->with([
            'status' => 'success',
            'message' => __('Başarıyla kaldırıldı'),
        ]);
    }

    protected function clearCache()
    {
        abort_unless(auth()->user()?->can('dashboard:access') && auth()->user()->can('settings:update'), 403);
        cache()->flush();

        $this->message(__('Önbellek temizlendi!'))->success();
    }

    public function render()
    {
        return view('livewire.events');
    }
}
