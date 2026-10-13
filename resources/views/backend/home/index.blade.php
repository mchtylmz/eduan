@extends('backend.layouts.app')

@push('style')
    <link rel="stylesheet" href="{{ asset('backend/assets/dashboard.css') }}?v={{ config('app.version') }}">
@endpush

@section('content')
    <div class="admin-dashboard">
        <div class="dashboard-freshness">
            <i class="fa fa-clock" aria-hidden="true"></i>
            <span>{{ __('dashboard.hourly') }}</span>
            <span class="dashboard-updated">{{ __('dashboard.updated') }}: {{ dateFormat($statistics['updated_at'], 'd/m/Y, H:i') }}</span>
        </div>
        <div class="dashboard-metrics">
            @include('backend.home.partials.metric', ['label' => __('Kayıtlı Öğrenciler'), 'value' => $statistics['registered'], 'tone' => 'blue', 'icon' => 'fa-users', 'hint' => $statistics['teacher'] ? __('dashboard.active_students') : __('dashboard.normal_students'), 'url' => auth()->user()->can('users:view') ? route('admin.users.index', ['status' => 'active', 'student_group' => $statistics['teacher'] ? null : 'normal']) : null])
            @include('backend.home.partials.metric', ['label' => __('Premium Öğrenciler'), 'value' => $statistics['premium'], 'tone' => 'violet', 'icon' => 'fa-user-graduate', 'hint' => __('dashboard.active_students'), 'url' => auth()->user()->can('users:view') ? route('admin.users.index', ['status' => 'active', 'student_group' => 'premium']) : null])
            @include('backend.home.partials.metric', ['label' => __('dashboard.new_students'), 'value' => $statistics['new_students'], 'tone' => 'green', 'icon' => 'fa-user-plus', 'hint' => __('dashboard.last_seven_days'), 'url' => auth()->user()->can('users:view') ? route('admin.users.index', ['status' => 'active', 'student_group' => 'all', 'registered_since' => $statistics['new_students_from']]) : null])
            @unless($statistics['teacher'])
                @include('backend.home.partials.metric', ['label' => __('Yeni Mesajlar'), 'value' => $statistics['messages'], 'tone' => 'amber', 'icon' => 'fa-message', 'hint' => __('dashboard.unread'), 'url' => auth()->user()->can('contacts:view') ? route('admin.contacts.index') : null])
            @endunless
        </div>
        <div class="dashboard-panels">
            @if($statistics['ai'] !== null)
                @include('backend.home.partials.ranking', ['title' => __('Popüler Yapay Zeka Konuları'), 'rows' => $statistics['ai'], 'icon' => 'fa-wand-magic-sparkles', 'metric' => __('Görüntüleme'), 'kind' => 'ai'])
            @endif
            @include('backend.home.partials.ranking', ['title' => __('En Çok Görünütülenen Testler'), 'rows' => $statistics['hits'], 'icon' => 'fa-eye', 'metric' => __('Görüntüleme'), 'kind' => 'hits'])
            @include('backend.home.partials.ranking', ['title' => __('En Çok Yanıtlanan Testler'), 'rows' => $statistics['results'], 'icon' => 'fa-list-check', 'metric' => __('Soru Sayısı'), 'kind' => 'results'])
            @unless($statistics['teacher'])
                @include('backend.home.partials.ranking', ['title' => __('En Çok Görüntülenen Dersler'), 'rows' => $statistics['lessons'], 'icon' => 'fa-book-open', 'metric' => __('Görüntüleme'), 'kind' => 'lessons'])
                @include('backend.home.partials.users', ['title' => __('Yeni Kayıt olan Kullanıcılar'), 'rows' => $statistics['new_users'], 'lastLogins' => false])
                @include('backend.home.partials.users', ['title' => __('Son Giriş Yapan Kullanıcılar'), 'rows' => $statistics['last_logins'], 'lastLogins' => true])
            @endunless
        </div>
    </div>
@endsection
