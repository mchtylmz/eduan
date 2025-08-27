@extends('frontend.layouts.app')
@section('content')
    <section class="contact-area pt-30 pb-30">
        <div class="container ps-1 pe-1">
            <div class="row">
                <div class="col-lg-6 ps-1 pe-1">
                    <div class="accordion" id="accordionAiOpen">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="panelAi-headingQuestion">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelAi-collapseQuestion" aria-expanded="true" aria-controls="panelAi-collapseQuestion">
                                    {{ __('Soru') }}
                                </button>
                            </h2>
                            <div id="panelAi-collapseQuestion" class="accordion-collapse collapse show" aria-labelledby="panelAi-headingQuestion">
                                <div class="accordion-body">
                                    <div class="title">
                                        <h5 class="mb-3">{{ $question->title }}</h5>
                                    </div>
                                    <div class="attachment text-left">
                                        @if($attachment = $question->attachment)
                                            <img class="w-100"
                                                 style="max-width: 600px; object-fit: contain"
                                                 src="{{ getImage($attachment) }}"
                                                 alt="{{ __('Soru') }}"/>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="panelAi-headingSolution">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelAi-collapseSolution" aria-expanded="false" aria-controls="panelAi-collapseSolution">
                                    {{ __('Soru Çözümü') }}
                                </button>
                            </h2>
                            <div id="panelAi-collapseSolution" class="accordion-collapse collapse" aria-labelledby="panelAi-headingSolution">
                                <div class="accordion-body">
                                    <div class="solution">
                                        <div class="attachment text-left">
                                            <img class="w-100"
                                                 style="max-width: 600px; object-fit: contain"
                                                 src="{{ getImage($question->solution) }}?v={{ time() }}"
                                                 alt="{{ __('Soru Çözümü') }}"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 ps-1 pe-1">
                    <div class="accordion" id="accordionAiOpen">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="panelAi-headingAi">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelAi-collapseAi" aria-expanded="true" aria-controls="panelAi-collapseAi">
                                    {{ __('Yapay Zeka Yanıtı') }}
                                </button>
                            </h2>
                            <div id="panelAi-collapseAi" class="accordion-collapse collapse show" aria-labelledby="panelAi-headingAi">
                                <div class="accordion-body">
                                    @livewire('frontend.ai.solution-ai', ['question' => $question, 'user' => user()])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('script')
    <script>
        setTimeout(() => {
            $('.solution-ai-button').trigger('click');
        }, 1000)
    </script>
@endpush
