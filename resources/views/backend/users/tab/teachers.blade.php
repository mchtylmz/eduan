<div class="mb-3">
    @if($user->hasPermissionTo(\App\Enums\RoleTypeEnum::TEACHER->value))
        @can('users:student-add')
            <livewire:users.student-add-form :teacherId="$user->id"/>
        @endcan
        <livewire:users.students-table :teacherId="$user->id"/>
    @else
        @can('users:teacher-add')
            <livewire:users.teacher-add-form :studentId="$user->id"/>
        @endcan
        <livewire:users.teachers-table :studentId="$user->id"/>
    @endif
</div>
