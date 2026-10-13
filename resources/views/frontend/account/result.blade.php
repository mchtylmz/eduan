@extends('frontend.account.layout')
@section('account-content')
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
                            <span class="d-none d-sm-block">{{ $results->firstItem() + $loop->index }}. {{ __('Sonuç') }}</span>
                            <span class="d-block d-sm-none">{{ $results->firstItem() + $loop->index }}.</span>
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
                        <div class="" id="questions-{{ $result->id }}">
                            <div class="row mb-3">
                                <div class="col-lg-6 d-flex align-items-center flex-wrap gap-2">
                                    @if($result->point >= $result->passing_score)
                                        <i class="fa fa-check-double text-success fw-bold fa-2x"></i>
                                        <p class="my-2 my-sm-0 text-success fw-bold">
                                            {{ __('Tebrikler, sınavı geçtin') }} 👏🏻
                                        </p>
                                    @else
                                        <i class="fa fa-circle-exclamation text-danger fw-bold fa-2x"></i>
                                        <p class="my-2 my-sm-0 text-danger fw-bold">
                                            {{ __('Sınavı geçemedin, çalışmaya devam etmelisin!') }}
                                        </p>
                                    @endif
                                </div>
                                <div class="col-lg-6">
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
                                </div>
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

                            @foreach($sections as $section)
                                <div
                                    class="block bg-body-light mb-2 px-3 py-3 rounded-2 d-flex align-items-center gap-2">
                                    <span class="badge bg-dark">{{ $loop->iteration }}</span>
                                    <p class="my-0 text-dark fw-bold">{{ $section->name }}</p>
                                </div>

                                <div class="accordion" id="accordionSections{{ $result->id }}-{{ $section->id }}">
                                    @php $questionIndex = 1; @endphp
                                    @foreach($section->parents as $parent)
                                        @php
                                            $detail = collect($result->details)->where('section_id', $parent->id)->first();
                                            $detail = collect($detail);

                                            $content = $parent;
                                            $detailQuestion = $result->details->firstWhere('question_id', $content->getMeta('questionId', 0));
                                            if (\App\Enums\TestSectionTypeEnum::QUESTION->is($content->type) && !$detailQuestion) {
                                                continue;
                                            }
                                        @endphp

                                        <div class="accordion-item mb-1">
                                            <h2 class="accordion-header" id="result-heading{{ $result->id }}-{{ $parent->id }}">
                                                <button class="accordion-button collapsed" type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#result-collapse{{ $result->id }}-{{ $parent->id }}"
                                                        aria-expanded="false"
                                                        aria-controls="result-collapse{{ $result->id }}-{{ $parent->id }}">
                                                    @if(\App\Enums\TestSectionTypeEnum::QUESTION->is($parent->type))

                                                        @if($detail->get('point') != 0)
                                                            <small
                                                                class="text-white py-1 px-2 me-2 bg-secondary">
                                                                {{ $detail->get('point') }} {{ __('Puan') }}
                                                            </small>
                                                        @endif

                                                        <small @class([
                                                'text-white py-1 px-2 me-2',
                                                'bg-danger' => \App\Enums\YesNoEnum::NO->value == $detail->get('correct'),
                                                'bg-success' => \App\Enums\YesNoEnum::YES->value == $detail->get('correct'),
                                                'bg-warning' => \App\Enums\YesNoEnum::EMPTY->value == $detail->get('correct'),
                                            ])>
                                                            @if(\App\Enums\YesNoEnum::YES->value == $detail->get('correct'))
                                                                {{ __('Doğru') }}
                                                            @elseif(\App\Enums\YesNoEnum::NO->value == $detail->get('correct'))
                                                                {{ __('Yanlış') }}
                                                            @else
                                                                {{ __('Boş') }}
                                                            @endif
                                                        </small>

                                                        <span>{{ __('Soru') }} {{ $questionIndex }}</span>
                                                        @php $questionIndex++; @endphp
                                                    @else
                                                        <span>{{ $parent->name }}</span>
                                                    @endif
                                                </button>
                                            </h2>
                                            <div id="result-collapse{{ $result->id }}-{{ $parent->id }}"
                                                 class="accordion-collapse collapse"
                                                 aria-labelledby="result-heading{{ $result->id }}-{{ $parent->id }}"
                                                 data-bs-parent="#accordionSections{{ $result->id }}-{{ $section->id }}">
                                                <div class="accordion-body">

                                                    @if(\App\Enums\TestSectionTypeEnum::CONTENT->is($content?->type))
                                                        <div class="text-dark exams-sections-content">
                                                            {!! html_entity_decode($content->getMeta('content', '')) !!}
                                                        </div>
                                                    @endif

                                                    @if(\App\Enums\TestSectionTypeEnum::PDF->is($content?->type))
                                                        <div class="text-dark exams-sections-file w-100">
                                                            <iframe style="min-height: 540px;"
                                                                    src="{{ asset($content->getMeta('meta_file', '')) }}#toolbar=1"
                                                                    class="w-100 border-0" allowfullscreen
                                                                    allowtransparency></iframe>
                                                        </div>
                                                    @endif

                                                    @if(\App\Enums\TestSectionTypeEnum::QUESTION->is($content->type) && $detail = $detailQuestion)
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
                                                                    {{ $detail->answer?->title ?? __('Boş')}}
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
                                                                <x-ai.button :questionCode="$question->code"/>
                                                            @endcan

                                                        @endif
                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                            @endforeach

                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    {{ $results->links('components.frontend.pagination') }}
@endsection
