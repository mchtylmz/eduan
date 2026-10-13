<?php

namespace App\Livewire\Users;

use App\Actions\Users\AuthorizeStudentAccess;
use App\Enums\RoleTypeEnum;
use App\Enums\YesNoEnum;
use App\Helpers\TableCache;
use App\Models\Role;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class UserTable extends Component
{
    use CustomLivewireAlert, ServerTableActions;

    protected string $resource = 'users';

    protected function builder(): Builder
    {
        return User::with('roles')->withCount(['students', 'teachers'])
            ->when(auth()->user()->can(RoleTypeEnum::TEACHER->value),
                fn ($query) => $query->whereIn('users.id', auth()->user()->students()->select('users.id')));
    }

    public function render()
    {
        $record = $this->selectedRecord();
        $metrics = [];
        if ($record && ! $record->can(RoleTypeEnum::ADMIN->value)) {
            if ($record->can(RoleTypeEnum::TEACHER->value)) {
                $metrics[__('tables.student_count')] = $record->students_count;
            } elseif ($record->can(RoleTypeEnum::USER->value)) {
                $metrics[__('tables.teacher_count')] = $record->teachers_count;
            }
        }

        return view('livewire.backend.users.server-table', [
            'roles' => TableCache::options('roles', fn () => Role::orderBy('name')
                ->when(auth()->user()->can(RoleTypeEnum::TEACHER->value),
                    fn ($query) => $query->permission(RoleTypeEnum::USER->value))
                ->pluck('name', 'name')->all()),
            'metrics' => $metrics,
            'details' => $record ? [__('Kullanıcı Adı') => $record->username, __('İsim') => $record->name,
                __('Soyisim') => $record->surname, __('tables.email') => $record->email,
                __('Yetki') => $record->roles->pluck('name')->implode(', '), __('Durum') => $record->status->name(),
                __('E-posta Onay') => YesNoEnum::YES->is($record->email_verified) ? __('Onaylı') : __('Onaylı Değil'),
                __('Son Giriş') => $record->last_login_at ? dateFormat($record->last_login_at) : '-',
                __('Kayıt Tarihi') => dateFormat($record->created_at), ...$metrics] : [],
        ]);
    }

    public function delete(User $user): ?bool
    {
        $this->requireTableAccess('delete');
        AuthorizeStudentAccess::run($user, 'users:delete');
        if (auth()->user()->cannot('users:delete')) {
            $this->message(__('Kullanıcı silinemez, yetkiniz bulunmuyor!'))->error();

            return false;
        }

        if ($user->id == auth()->id()) {
            $this->message(__('Kendi kullanıcınızı kaldıramazsınız!'))->error();

            return false;
        }

        $this->message(__('Kullanıcı giriş yapabilme kaldırıldı!'))->success();

        $deleted = $user->delete();
        $this->dispatch('refresh-server-table', table: $this->resource);

        return $deleted;
    }
}
