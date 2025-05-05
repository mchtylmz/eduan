@extends('frontend.layouts.app')
@section('content')

    <!-- lessons area start -->
    <section class="h10_category-area pt-40 pb-50 bg-white">
        <div class="container">
            <div class="py-2 px-3 mb-3" style="background-color: #f6f7f99c !important">
                <h5 class="mb-0 px-3">{{ $test->name }}</h5>
            </div>

            <div class="course_details-wrap mb-55">
                <div class="course_details-tab-button">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        @foreach($results as $result)
                            <li class="nav-item" role="presentation" style="width: 20%">
                                <button class="nav-link {{ $loop->last ? 'active': '' }}"
                                        id="pills-{{ $loop->index }}-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-{{ $loop->index }}" type="button" role="tab"
                                        aria-controls="pills-{{ $loop->index }}" aria-selected="false">
                                    <i class="fa fa-fw fa-poll d-none d-sm-block"></i>
                                    <span class="d-none d-sm-block">{{ $loop->iteration }}. {{ __('Sonuç') }}</span>
                                    <span class="d-block d-sm-none">{{ $loop->iteration }}.</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="course_details-tab-content">
                    <div class="tab-content h4_faq-area" id="pills-tabContent">
                        @foreach($results as $result)
                            <div class="tab-pane h4_faq-content fade {{ $loop->last ? 'show active': '' }}"
                                 id="pills-{{ $loop->index }}" role="tabpanel" aria-labelledby="pills-home-tab"
                                 tabindex="0">
                                <div class="accordion" id="questions-{{ $result->id }}">
                                    <div class="mb-1 text-end d-grid">
                                        <small class="px-3 py-0 text-end text-dark mb-0">
                                            {{ __('Sınava Katılma Zamanı') }}:
                                            {{ dateFormat($result->created_at, 'd/m/Y H:i, l') }}
                                        </small>
                                        <small class="px-3 py-0 text-end text-dark mb-0">
                                            {{ __('Sınava Tamamlama Zamanı') }}:
                                            {{ dateFormat($result->completed_at, 'd/m/Y H:i, l') }}
                                        </small>
                                        <small class="px-3 py-0 text-end text-dark mb-0">
                                            {{ __('Sınav Süresi') }}:
                                            {{ formatSecondToTime(secondToTime(strtotime($result->completed_at) - strtotime($result->created_at))) }}
                                        </small>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-lg-3 text-center mb-0">
                                            <p class="px-4 py-2 bg-light fw-medium text-dark rounded-1 border border-secondary border-1 mb-2 mt-1 d-flex align-items-center justify-content-between">
                                                {{ __('Sınav Puanı') }}
                                                <strong class="bg-dark rounded-2 text-white py-1 px-3">
                                                    {{ $result->point }} {{ __('Puan') }}
                                                </strong>
                                            </p>
                                        </div>
                                        <div class="col-lg-3 text-center mb-0">
                                            <p class="px-4 py-2 bg-warning fw-medium text-dark rounded-1 border border-warning border-1 mb-2 mt-1 d-flex align-items-center justify-content-between">
                                                {{ __('Toplam Soru') }}
                                                <strong class="bg-light rounded-2 text-dark py-1 px-3">
                                                    {{ $result->question_count }}
                                                </strong>
                                            </p>
                                        </div>
                                        <div class="col-lg-3 text-center mb-0">
                                            <p class="px-4 py-2 bg-success fw-medium text-white rounded-1 border border-success border-1 mb-2 mt-1 d-flex align-items-center justify-content-between">
                                                {{ __('Doğru Yanıt') }}
                                                <strong class="bg-light rounded-2 text-dark py-1 px-3">
                                                    {{ $result->correct_count }}
                                                </strong>
                                            </p>
                                        </div>
                                        <div class="col-lg-3 text-center mb-0">
                                            <p class="px-4 py-2 bg-danger fw-medium text-white rounded-1 border border-danger border-1 mb-2 mt-1 d-flex align-items-center justify-content-between">
                                                {{ __('Yanlış Yanıt') }}
                                                <strong class="bg-light rounded-2 text-dark py-1 px-3">
                                                    {{ $result->incorrect_count }}
                                                </strong>
                                            </p>
                                        </div>
                                    </div>

                                    @foreach($result->details as $detail)
                                        <div class="accordion-item mb-1 mt-1">
                                            <h2 class="accordion-header" id="heading{{ $detail->id }}">
                                                <button class="accordion-button collapsed d-flex align-items-center"
                                                        type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#collapse{{ $detail->id }}"
                                                        aria-expanded="false" aria-controls="collapse{{ $detail->id }}">
                                                    <small class="text-white py-1 px-2 me-2 bg-secondary">
                                                        {{ $detail->point }} {{ __('Puan') }}
                                                    </small>
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
                                                                     src="{{ getImage($solution) }}"
                                                                     alt="{{ __('Soru Çözümü') }}"/>
                                                            </div>
                                                        @endif
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
    </section>
    <!-- lessons area end -->

@endsection
