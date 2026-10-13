<?php

namespace App\Livewire\Frontend\Home;

use App\Models\Page;
use Illuminate\Support\Arr;
use Livewire\Component;

class BlogSection extends Component
{
    public Page $page;

    public array $content;

    public function mount(Page $page)
    {
        $this->page = $page;
        $this->content = Arr::only((array) $page->content, ['blogCount', 'blogDescription', 'blogTitle']);
    }

    public function render()
    {
        return view('livewire.frontend.home.blog-section');
    }
}
