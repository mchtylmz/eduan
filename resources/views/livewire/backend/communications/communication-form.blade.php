<div class="block block-rounded" data-communication-form data-selected-recipients="{{ json_encode($recipients) }}">
    <div class="block-header block-header-default">
        <h1 class="block-title">{{ __('notifications.communications') }}</h1>
        <div class="block-options">
            @if($step === 0)
                @can('communications:send')<button class="btn btn-alt-primary" type="button" wire:click="start" wire:loading.attr="disabled"><i class="fa fa-plus me-1" aria-hidden="true"></i>{{ __('notifications.new_message') }}</button>@endcan
            @else
                <button class="btn btn-alt-secondary" type="button" wire:click="cancel" wire:loading.attr="disabled">{{ __('notifications.cancel') }}</button>
            @endif
        </div>
    </div>
    <div class="block-content pb-4">
        @if($notice)<p class="alert alert-success" role="status">{{ $notice }}</p>@endif
        @error('communication')<p class="alert alert-warning" role="alert">{{ $message }}</p>@enderror
        <div wire:key="communication-history-panel" @class(['d-none' => $step !== 0])>
            <div data-server-table-page="communication-history" @include('components.admin.table-messages', ['confirm' => ''])>
                <x-admin.filter-bar>
                    <x-admin.filter-select id="communication-history-channel" name="channel" :label="__('notifications.channel')" :options="['email'=>__('notifications.email'),'push'=>__('notifications.push')]" />
                    <x-admin.filter-select id="communication-history-state" name="delivery_status" :label="__('study.state')" :options="collect(['pending','scheduled','sending','sent','failed','skipped'])->mapWithKeys(fn($state)=>[$state=>__('notifications.status_'.$state)])->all()" />
                </x-admin.filter-bar>
                <x-admin.server-table id="communication-history-table" :url="route('admin.communications.history')" sort-name="created_at" sort-order="desc" min-width="760px">
                    <thead><tr>
                        <th data-field="recipient" data-formatter="eduanTableTextFormatter">{{ __('notifications.recipient') }}</th>
                        <th data-field="channel" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('notifications.channel') }}</th>
                        <th data-field="title" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('notifications.subject') }}</th>
                        <th data-field="status" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.state') }}</th>
                        <th data-field="created_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.date') }}</th>
                    </tr></thead>
                </x-admin.server-table>
            </div>
        </div>
        @if($step !== 0)
            <ol class="nav nav-pills nav-fill mb-4" aria-label="{{ __('notifications.compose_steps') }}">
                @foreach([1=>'recipients',2=>'message_step',3=>'preview_step'] as $number=>$label)
                    <li class="nav-item"><span @class(['nav-link','active'=>$step===$number,'text-muted'=>$step!==$number]) @if($step===$number) aria-current="step" @endif>{{ $number }}. {{ __('notifications.'.$label) }}</span></li>
                @endforeach
            </ol>
            @if($step === 1)
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 bg-body-light border rounded p-3 mb-3 communication-step-actions">
                    <span data-selection-count data-template="{{ __('notifications.selected_count', ['count'=>':count']) }}">{{ __('notifications.selected_count', ['count'=>count($recipients)]) }}</span>
                    <button class="btn btn-alt-primary" type="button" wire:click="next" wire:loading.attr="disabled">{{ __('notifications.continue') }}<i class="fa fa-arrow-right ms-1" aria-hidden="true"></i></button>
                </div>
                <x-badge.error field="recipients" />
                <div data-server-table-page="communication-recipients" data-table-url-state="false" @include('components.admin.table-messages', ['confirm' => ''])>
                    <p class="text-muted">{{ __('notifications.selection_help') }}</p>
                    <x-admin.server-table id="communication-recipients-table" :url="route('admin.communications.recipients')" sort-name="name" min-width="560px">
                        <thead><tr><th data-field="selected" data-checkbox="true"></th><th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim') }}</th><th data-field="surname" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soyisim') }}</th><th data-field="username" data-formatter="eduanTableTextFormatter">{{ __('Kullanıcı Adı') }}</th></tr></thead>
                    </x-admin.server-table>
                </div>
            @elseif($step === 2)
                <form wire:submit="next">
                    <p class="text-muted">{{ __('notifications.selected_count', ['count'=>count($recipients)]) }}</p>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label" for="communication-channel">{{ __('notifications.channel') }}</label><select class="form-select" id="communication-channel" wire:model="channel"><option value="email">{{ __('notifications.email') }}</option><option value="push">{{ __('notifications.push') }}</option></select><x-badge.error field="channel" /></div>
                        <div class="col-md-6"><label class="form-label" for="communication-locale">{{ __('Dil') }}</label><select class="form-select" id="communication-locale" wire:model="locale">@foreach(['tr'=>'Türkçe','nl'=>'Nederlands','en'=>'English','fr'=>'Français'] as $code=>$name)<option value="{{ $code }}">{{ $name }}</option>@endforeach</select><x-badge.error field="locale" /></div>
                    </div>
                    <div class="mb-3"><label class="form-label" for="communication-title">{{ __('notifications.subject') }}</label><input class="form-control" id="communication-title" wire:model="title" maxlength="160"><x-badge.error field="title" /></div>
                    <div class="mb-3"><label class="form-label" for="communication-body">{{ __('notifications.body') }}</label><textarea class="form-control" id="communication-body" wire:model="body" rows="6" maxlength="10000"></textarea><x-badge.error field="body" /></div>
                    <p class="small text-muted">{{ __('notifications.variables_help') }}</p>
                    <div class="d-flex gap-2"><button class="btn btn-alt-secondary" type="button" wire:click="previous" wire:loading.attr="disabled">{{ __('notifications.back') }}</button><button class="btn btn-alt-primary" type="submit" wire:loading.attr="disabled">{{ __('notifications.preview_step') }}<i class="fa fa-arrow-right ms-1" aria-hidden="true"></i></button></div>
                </form>
            @elseif($step === 3)
                <dl class="row mb-4">
                    <dt class="col-sm-3 mb-2">{{ __('notifications.recipients') }}</dt><dd class="col-sm-9 mb-2">{{ __('notifications.selected_count', ['count'=>$preview['count']]) }}<div class="small text-muted">{{ implode(', ', $preview['names']) }}{{ $preview['count'] > 5 ? ' …' : '' }}</div></dd>
                    <dt class="col-sm-3 mb-2">{{ __('notifications.channel') }}</dt><dd class="col-sm-9 mb-2">{{ __('notifications.'.$preview['channel']) }}</dd>
                    <dt class="col-sm-3 mb-2">{{ __('Dil') }}</dt><dd class="col-sm-9 mb-2">{{ ['tr'=>'Türkçe','nl'=>'Nederlands','en'=>'English','fr'=>'Français'][$preview['locale']] }}</dd>
                </dl>
                <p class="small text-muted">{{ __('notifications.preview_sample', ['name'=>$preview['sample']]) }}</p>
                <div class="border rounded p-3 mb-4"><h2 class="h5 text-break">{{ $preview['title'] }}</h2><div class="communication-preview-message text-break">{{ $preview['body'] }}</div></div>
                <form wire:submit="enqueue" class="d-flex gap-2"><button class="btn btn-alt-secondary" type="button" wire:click="previous" wire:loading.attr="disabled">{{ __('notifications.back') }}</button><button class="btn btn-alt-primary" type="submit" wire:loading.attr="disabled"><i class="fa fa-paper-plane me-1" aria-hidden="true"></i>{{ __('notifications.send') }}</button></form>
            @endif
        @endif
    </div>
    @once @push('script')<script src="{{ asset('backend/assets/communications.js') }}?v={{ config('app.version') }}"></script>@endpush @endonce
</div>
