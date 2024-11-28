<!-- user-password -->
@can('users:update-password')
    @livewire('users.update-password-form', ['user' => $user])
@endcan
<!-- /user-password -->
