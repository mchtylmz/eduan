<?php

namespace App\Livewire\Frontend\Faqs;

use App\Models\Blog;
use App\Models\Faq;
use App\Models\Lesson;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class ListFaqs extends Component
{
    public int $limit = 99;

    #[Computed]
    public function faqs()
    {
        return Cache::remember(
            implode('_', [
                'frontend_faqs', $this->limit
            ]),
            60 * 60 * 60 * 365,
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
