@extends('frontend.layouts.app')
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverTests])
@endpush
@section('content')

    <!-- faq area start -->
    <div class="h4_faq-area pt-25 pb-75">
        <div class="container">
            <div class="py-2 px-3 mb-3" style="background-color: #f6f7f99c !important">
                <h5 class="mb-0 px-3">{{ $exam->name }}</h5>
            </div>

            <div class="course_details-wrap mb-55">
                <div class="course_details-tab-button">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        @foreach($results as $result)
                            <li class="nav-item" role="presentation" style="width: 20%">
                                <button class="nav-link {{ $loop->first ? 'active': '' }}"
                                        id="pills-{{ $loop->index }}-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-{{ $loop->index }}" type="button" role="tab"
                                        aria-controls="pills-{{ $loop->index }}" aria-selected="false">
                                    <i class="fa fa-fw fa-poll"></i>
                                    <span>{{ $loop->iteration }}. {{ __('Sonuç') }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="course_details-tab-content">
                    <div class="tab-content h4_faq-area" id="pills-tabContent">
                        @foreach($results as $result)
                            <div class="tab-pane h4_faq-content fade {{ $loop->first ? 'show active': '' }}"
                                 id="pills-{{ $loop->index }}" role="tabpanel" aria-labelledby="pills-home-tab"
                                 tabindex="0">
                                <div class="accordion" id="questions-{{ $result->id }}">
                                    @foreach($result->details as $detail)
                                        <div class="accordion-item mb-1">
                                            <h2 class="accordion-header" id="heading{{ $detail->id }}">
                                                <button class="accordion-button collapsed d-flex align-items-center"
                                                        type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#collapse{{ $detail->id }}"
                                                        aria-expanded="false" aria-controls="collapse{{ $detail->id }}">
                                                    <small @class([
                                                        'text-white py-1 px-2 me-2',
                                                        'bg-danger' => \App\Enums\YesNoEnum::NO->is($detail->correct),
                                                        'bg-success' => \App\Enums\YesNoEnum::YES->is($detail->correct),
                                                    ])>
                                                        {{ \App\Enums\YesNoEnum::YES->is($detail->correct) ? __('Doğru') : __('Yanlış') }}
                                                    </small>
                                                    <span>{{ $loop->iteration }}. {{ __('Soru') }}</span>
                                                </button>
                                            </h2>
                                            <div id="collapse{{ $detail->id }}" class="accordion-collapse collapse"
                                                 aria-labelledby="heading{{ $detail->id }}"
                                                 data-bs-parent="#questions-{{ $result->id }}">
                                                <div class="accordion-body">
                                                    @if($question = $detail->question)
                                                        <div class="question">
                                                            <h5 class="mb-3 px-3">{{ $question->title }}</h5>
                                                            @if($attachment = $question->attachment)
                                                                <img class="w-100"
                                                                     style="max-width: 600px; object-fit: contain"
                                                                     src="{{ getImage($attachment) }}"
                                                                     alt="{{ __('Soru') }}"/>
                                                            @endif
                                                        </div>
                                                        <div class="answer bg-body-light py-2 px-3 mt-3">
                                                            <h5 class="mb-1 text-dark">
                                                                {{ __('Yanıtınız') }} :
                                                                {{ $detail->answer?->title }}
                                                            </h5>
                                                        </div>
                                                        @if($solution = $question->solution)
                                                            <div class="solution px-3 mt-3">
                                                                <h5 class="mb-3">{{ __('Çözüm') }}: </h5>
                                                                <img class="w-100"
                                                                     style="max-width: 720px; object-fit: contain"
                                                                     src="{{ getImage($solution) }}?v={{ time() }}"
                                                                     alt="{{ __('Soru Çözümü') }}"/>
                                                            </div>
                                                        @endif

                                                        @can('ai:solution')
                                                            <x-ai.button :questionCode="$question->code" />
                                                        @endcan
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- faq area end -->

@endsection
