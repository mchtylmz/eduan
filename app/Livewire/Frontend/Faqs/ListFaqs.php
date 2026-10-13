<?php

namespace App\Livewire\Frontend\Faqs;

use App\Helpers\FrontendCache;
use App\Models\Faq;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ListFaqs extends Component
{
    public int $limit = 99;

    #[Computed]
    public function faqs()
    {
        return FrontendCache::remember(
            'faqs', [$this->limit],
            function () {
                return Faq::active()->orderBy('sort')->limit($this->limit)->get();
            }
        );
    }

    public function render()
    {
        return view('livewire.frontend.faqs.list-faqs');
    }
}
