<?php

namespace App\Livewire\Frontend\Home;

use App\Models\Page;
use Livewire\Attributes\Lazy;
use Livewire\Component;

//#[Lazy(isolate: true)]
class WelcomeSection extends Component
{
    public Page $page;
    public array $content;

    public function mount(Page $page)
    {
        $this->page = $page;
        $this->content = collect($page->content)->toArray();
    }

    public function render()
    {
        return view('livewire.frontend.home.welcome-section');
    }
}
