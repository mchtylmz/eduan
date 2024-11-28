<?php

namespace App\Livewire\Frontend\Account;

use App\Actions\Exams\UpdateExamFavoriteForUserAction;
use App\Models\Exam;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy(isolate: true)]
class FavoriteTests extends Component
{
    use WithPagination, CustomLivewireAlert;

    protected string $paginationTheme = 'bootstrap';

    public User $user;
    public bool $showPaginate = true;
    public int $paginate = 21;

    public function mount(): void
    {
        $this->user = User::find(auth()->id());
    }

    #[Computed]
    public function tests()
    {
        return $this->user->favoriteExams()
            ->withCount(['questions' => fn($query) => $query->active()])
            ->with(['language', 'userResults'])
            ->active()
            ->paginate($this->paginate);
    }

    public function toggleBookmark(Exam $exam): void
    {
        UpdateExamFavoriteForUserAction::run(
            exam: $exam,
            user: $this->user,
            action: 'detach'
        );

        $this->message(__('Seçilen test favoriden çıkarıldı.'))->success();
    }

    public function render()
    {
        return view('livewire.frontend.account.favorite-tests');
    }
}
