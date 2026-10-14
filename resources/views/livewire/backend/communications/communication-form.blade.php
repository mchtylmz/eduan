<div data-communication-form>
    @if($notice)<p class="alert alert-success" role="status">{{ $notice }}</p>@endif
    @error('communication')<p class="alert alert-warning">{{ $message }}</p>@enderror
    <div data-server-table-page="communication-recipients" @include('components.admin.table-messages', ['confirm' => ''])>
        <p class="text-muted">{{ __('notifications.selection_help') }}</p>
        <x-admin.server-table id="communication-recipients-table" :url="route('admin.communications.recipients')" sort-name="name" min-width="560px">
            <thead><tr><th data-field="selected" data-checkbox="true"></th><th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim') }}</th><th data-field="surname" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soyisim') }}</th><th data-field="username" data-formatter="eduanTableTextFormatter">{{ __('Kullanıcı Adı') }}</th></tr></thead>
        </x-admin.server-table>
    </div>
    @can('communications:send')
    <form wire:submit="enqueue" class="mt-4">
        <p data-selection-count data-template="{{ __('notifications.selected_count', ['count' => ':count']) }}">{{ __('notifications.selected_count', ['count' => count($recipients)]) }}</p><x-badge.error field="recipients" />
        <div class="row g-3"><div class="col-md-6"><label class="form-label" for="communication-channel">{{ __('notifications.channel') }}</label><select class="form-select" id="communication-channel" wire:model="channel"><option value="email">{{ __('notifications.email') }}</option><option value="push">{{ __('notifications.push') }}</option></select></div>
        <div class="col-md-6"><label class="form-label" for="communication-locale">{{ __('Dil') }}</label><select class="form-select" id="communication-locale" wire:model="locale">@foreach(['tr'=>'Türkçe','nl'=>'Nederlands','en'=>'English','fr'=>'Français'] as $code=>$name)<option value="{{ $code }}">{{ $name }}</option>@endforeach</select></div></div>
        <label class="form-label mt-3" for="communication-title">{{ __('notifications.subject') }}</label><input class="form-control" id="communication-title" wire:model="title" maxlength="160"><x-badge.error field="title" />
        <label class="form-label mt-3" for="communication-body">{{ __('notifications.body') }}</label><textarea class="form-control" id="communication-body" wire:model="body" rows="5" maxlength="10000"></textarea><x-badge.error field="body" />
        <p class="small text-muted mt-2">{{ __('notifications.variables_help') }}</p>
        <button class="btn btn-primary" type="submit" wire:loading.attr="disabled" wire:confirm="{{ __('notifications.send_confirm') }}">{{ __('notifications.send') }}</button>
    </form>
    @endcan
    <h2 class="h5 mt-4">{{ __('notifications.delivery_history') }}</h2>
    <div class="table-responsive"><table class="table"><thead><tr><th>{{ __('notifications.channel') }}</th><th>{{ __('notifications.subject') }}</th><th>{{ __('study.state') }}</th><th>{{ __('study.date') }}</th></tr></thead><tbody>
        @forelse($recent as $item)<tr><td>{{ __('notifications.'.$item->channel) }}</td><td>{{ $item->title }}</td><td>{{ __('notifications.status_'.$item->status) }}</td><td>{{ dateFormat($item->created_at) }}</td></tr>@empty<tr><td colspan="4">{{ __('notifications.no_messages') }}</td></tr>@endforelse
    </tbody></table></div>
    @once @push('script')<script src="{{ asset('backend/assets/communications.js') }}?v={{ config('app.version') }}"></script>@endpush @endonce
</div>
