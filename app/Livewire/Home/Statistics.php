<?php

namespace App\Livewire\Home;

use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Models\Contact;
use App\Models\Exam;
use App\Models\ExamReview;
use App\Models\Lesson;
use App\Models\Newsletter;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Arr;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class Statistics extends Component
{
    public string $section;
    public int $time = 60 * 60 * 2;
    public array $data = [];

    public function mount(string $section): void
    {
        $this->section = $section;

        if (method_exists($this, $this->section)) {
            $this->{$this->section}();
        }
    }

    public function users(): void
    {
        $count = cache()->remember(
            'home_users',
            $this->time,
            fn() => User::active()->user()->count()
        );

        $this->data(
            count: $count,
            icon: 'fa-users',
            title: __('Kayıtlı Öğrenciler'),
            footerRoute: route('admin.users.index')
        );
    }

    public function usersPremium(): void
    {
        $count = cache()->remember(
            'home_premium_users',
            $this->time,
            fn() => User::active()->premiumUser()->count()
        );

        $this->data(
            count: $count,
            icon: 'fa-users',
            title: __('Premium Öğrenciler'),
            footerRoute: route('admin.users.index')
        );
    }

    public function contactMessages(): void
    {
        $count = cache()->remember(
            'home_contactMessages',
            $this->time,
            fn() => Contact::where('has_read', YesNoEnum::NO)->count()
        );

        $this->data(
            count: $count,
            icon: 'fa-message',
            title: __('Yeni Mesajlar'),
            footerRoute: route('admin.contacts.index')
        );
    }

    public function examReviews(): void
    {
        $count = cache()->remember(
            'home_examReviews',
            $this->time,
            fn() => ExamReview::where('has_read', YesNoEnum::NO)->count()
        );

        $this->data(
            count: $count,
            icon: 'fa-comments',
            title: __('Yeni Değerlendirmeler'),
            footerRoute: route('admin.exams.index')
        );
    }

    public function newsletterSubscribes(): void
    {
        $count = cache()->remember(
            'home_newsletterSubscribes',
            $this->time,
            fn() => Newsletter::count()
        );

        $this->data(
            count: $count,
            icon: 'fa-bullhorn',
            title: __('Bilgilendirme Aboneleri'),
            footerRoute: route('admin.newsletter.index')
        );
    }

    private function data(
        int $count, string $icon, string $title, ?string $footerText = null, ?string $footerRoute = null
    ): void
    {
        $this->data = [
            'count' => $count,
            'icon' => 'fa fa-fw ' . $icon,
            'description' => $title
        ];
        if ($footerRoute) {
            $this->data['footerText'] = $footerText ?? __('Tümünü Görüntüle');
            $this->data['footerRoute'] = $footerRoute;
        }
    }

    public function render()
    {
        return view('livewire.backend.home.statistics');
    }
}
