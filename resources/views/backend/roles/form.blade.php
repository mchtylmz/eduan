@props([
    'action' => $action ?? 'insert',
    'permissions' => \Spatie\Permission\Models\Permission::whereNotIn('name', \App\Enums\RoleTypeEnum::values())->get(),
    'translations' => [
        'view' => __('Görüntüle'),
        'add' => __('Ekle'),
        'update' => __('Düzenle'),
        'delete' => __('Sil / Kaldır'),
        'send' => __('Gönder'),
        'translate' => __('Çevir'),
        'solve' => __('Çözüm Yapabilir'),
        'access' => __('Erişebilir'),
        'update-password' => __('Parola Güncellemesi'),

        'blogs' => __('Bloglar'),
        'lessons' => __('Dersler'),
        'topics' => __('Konular'),
        'questions' => __('Soru Havuzu'),
        'exams' => __('Testler'),
        'users' => __('Kullanıcılar'),
        'roles' => __('Yetkiler'),
        'pages' => __('Sayfalar'),
        'newsletter' => __('Bilgilendirme Aboneleri'),
        'contacts' => __('İletişim Mesajları'),
        'languages' => __('Diller & Çeviriler'),
        'settings' => __('Ayarlar'),
        'dashboard' => __('Yönetim Paneli')
    ]
])
@php
$groups = collect($permissions)->mapToGroups(function ($item) {
    return [explode(':', $item->name)[0] => explode(':', $item->name)[1]];
})->all();
@endphp
<!-- row -->
<div class="row mb-3">

    <div class="col-lg-12">
        <div class="mb-3">
            <label class="form-label" for="name">{{ __('Yetki Adı') }}</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('Yetki Adı') }}.."
                   value="{{ $role->name ?? '' }}" required>
        </div>
    </div>

    <div class="col-lg-12">
        <label class="form-label" for="permission">{{ __('Yetki Grubu') }}</label>
    </div>
    <!-- role type -->
    @foreach(\App\Enums\RoleTypeEnum::cases() as $name => $value)
        <div class="col-lg-3 mb-3">
            <div class="form-check form-block">
                <input class="form-check-input"
                       type="radio"
                       value="{{ $value }}"
                       id="role-type_{{ $name }}"
                       name="permissions[]"
                    @checked(!empty($role) && $role->hasPermissionTo($value)) required>
                <label class="form-check-label" for="role-type_{{ $name }}">
                    <i class="{{ $value->icon() }} opacity-75 mx-2"></i>
                    <span class="fw-bold">{{ $value->name() }}</span>
                </label>
            </div>
        </div>
    @endforeach
    <!-- role type -->

    <div class="col-lg-12">
        <label class="form-label mb-2" for="permission">{{ __('İşlem İzinleri') }}</label>
    </div>

    @foreach($groups as $name => $permits)
        <div class="px-3 py-1">
            <div class="bg-body-light p-2 px-3">
                <p class="mb-0 fw-semibold fs-6">{{ $translations[$name] ?? $name }}</p>
            </div>
            <div class="permits px-3 py-2">
                <div class="row">
                    @foreach($permits as $permit)
                        @php $permission_name = $name.':'.$permit; @endphp
                        <div class="col-12 col-lg-3 mb-1">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" value="{{ $permission_name }}"
                                       id="permission_{{ $permission_name }}"
                                       name="permissions[]"
                                    @checked(!empty($role) && $role->hasPermissionTo($permission_name))>
                                <label class="form-check-label" for="permission_{{ $permission_name }}">
                                    {{ $translations[$permit] ?? $permit }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    {{------
    <hr>

    @foreach($permissions as $permission)
        <div class="col-lg-3 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" value="{{ $permission->name }}"
                       id="permission_{{ $permission->name }}"
                       name="permissions[]"
                    @checked(!empty($role) && $role->hasPermissionTo($permission->name))>
                <label class="form-check-label" for="permission_{{ $permission->name }}">
                    {{ $permission->name}}
                </label>
            </div>
        </div>
    @endforeach
    -----}}

</div>
<!-- row -->
