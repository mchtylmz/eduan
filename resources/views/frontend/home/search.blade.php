@extends('frontend.layouts.app')
@push('seo')
    @includeIf('components.seo.tags', [
        'title' => $title,
        'image' => settings()->siteLogo
    ])
@endpush
@section('content')
    <section class="breadcrumb-area bg-default" style="background-color: #f6f7f99c !important">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-8">
                    <div class="breadcrumb-content">
                        <form method="get"
                              action="{{ route('frontend.search') }}"
                              class="h2_banner-form"
                              style="max-width: none">
                            @csrf
                            <input type="text"
                                   placeholder="{{ __('Ara') }}..."
                                   name="search"
                                   minlength="3"
                                   value="{{ request('search') }}"
                                   required>
                            <input type="hidden" name="tab" value="{{ request('tab') ?: 'lessons' }}"/>
                            <button type="submit"><i class="fa-thin fa-magnifying-glass"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-area pt-30">
        <div class="container">
            <div class="course_details-wrap mb-55">
                <div class="course_details-tab-button">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        <!-- lessons -->
                        <li class="nav-item" role="presentation" style="width: 25%">
                            <a @class(['nav-link', 'active' => request('tab') == 'lessons']) type="button" role="tab"
                               href="{{ request()->fullUrlWithQuery(['tab' => 'lessons']) }}">
                                <i class="fa fa-fw fa-book"></i>
                                <span>{{ __('Dersler') }}</span>
                            </a>
                        </li>
                        <!-- /lessons -->
                        <!-- topics -->
                        <li class="nav-item" role="presentation" style="width: 25%">
                            <a @class(['nav-link', 'active' => request('tab') == 'topics']) type="button" role="tab"
                               href="{{ request()->fullUrlWithQuery(['tab' => 'topics']) }}">
                                <i class="fa fa-fw fa-book-reader"></i>
                                <span>{{ __('Konular') }}</span>
                            </a>
                        </li>
                        <!-- /topics -->
                        <!-- exams -->
                        <li class="nav-item" role="presentation" style="width: 25%">
                            <a @class(['nav-link', 'active' => request('tab') == 'exams']) type="button" role="tab"
                               href="{{ request()->fullUrlWithQuery(['tab' => 'exams']) }}">
                                <i class="fa fa-fw fa-pen"></i>
                                <span>{{ __('Testler') }}</span>
                            </a>
                        </li>
                        <!-- /exams -->
                        <!-- blogs -->
                        <li class="nav-item" role="presentation" style="width: 25%">
                            <a @class(['nav-link', 'active' => request('tab') == 'newspaper']) type="button" role="tab"
                               href="{{ request()->fullUrlWithQuery(['tab' => 'newspaper']) }}">
                                <i class="fa fa-fw fa-newspaper"></i>
                                <span>{{ __('Bloglar') }}</span>
                            </a>
                        </li>
                        <!-- /blogs -->
                    </ul>
                </div>
                <div class="course_details-tab-content">
                    <div class="tab-content h4_faq-area" id="pills-tabContent">
                        <div class="tab-pane h4_faq-content fade show active" role="tabpanel">

                            @switch(request('tab'))
                                @case('lessons')
                                    @livewire('frontend.lessons.list-lessons', [
                                        'visibilityLite' => true,
                                        'search' => true,
                                        'word' => request('search')
                                    ])
                                    @break

                                @case('topics')
                                    @livewire('frontend.lessons.list-topics', [
                                        'visibilityLite' => true,
                                        'search' => true,
                                        'word' => request('search')
                                    ])
                                    @break

                                @case('exams')
                                    @livewire('frontend.tests.list-tests', [
                                        'showHits' => false,
                                        'showPaginate' => true,
                                        'paginate' => 21,
                                        'showSearch' => false,
                                        'search' => true,
                                        'topic_id' => $topic->id ?? 0,
                                        'word' => request('search')
                                    ])
                                    @break

                                @case('newspaper')
                                    @livewire('frontend.blogs.list-blogs', ['search' => request('search')])
                                    @break
                            @endswitch

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
