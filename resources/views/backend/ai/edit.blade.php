@extends('backend.layouts.app')

@section('content')
    <div class="block block-rounded">
        <ul class="nav nav-tabs nav-tabs-alt" role="tablist">
            <li class="nav-item" role="presentation">
                <a @class(['nav-link py-3', 'active' => $activeTab == 'answer']) type="button" href="?tab=answer">
                    <i class="fa fa-magic-wand-sparkles mx-1"></i> {{ __('Yanıt Bilgileri') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a @class(['nav-link py-3', 'active' => $activeTab == 'usages']) type="button" href="?tab=usages">
                    <i class="fa fa-list-dots mx-1"></i> {{ __('Görüntülemeler') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a @class(['nav-link py-3', 'active' => $activeTab == 'votes']) type="button" href="?tab=votes">
                    <i class="fa fa-star mx-1"></i> {{ __('Değerlendirmeler') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a @class(['nav-link py-3', 'active' => $activeTab == 'edit']) type="button" href="?tab=edit">
                    <i class="fa fa-pen mx-1"></i> {{ __('Güncelle & Düzenle') }}
                </a>
            </li>
        </ul>

        <div class="block-content tab-content">
            <div class="tab-pane active show" tabindex="0">
                @switch($activeTab)
                    @case('answer')
                        @livewire('ai.answer-ai-detail', ['answerAI' => $answerAI])
                    @break
                    @case('usages')
                        <livewire:ai.user-usage-table answerAiId="{{ $answerAI->id }}"/>
                    @break
                    @case('votes')
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="block block-rounded block-link-pop mb-3 bg-primary-light">
                                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fa fa-vote-yea fs-3 text-white"></i>
                                        </div>
                                        <dl class="ms-3 text-end mb-0">
                                            <dt class="h3 fw-extrabold text-white mb-0">{{ $answerAI->votes()->count() }}</dt>
                                            <dd class="fs-sm fw-medium text-white mb-0">{{ __('Toplam') }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="block block-rounded block-link-pop mb-3 bg-warning">
                                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fa fa-vote-yea fs-3 text-white"></i>
                                        </div>
                                        <dl class="ms-3 text-end mb-0">
                                            <dt class="h3 fw-extrabold text-white mb-0">{{ round($answerAI->votes()->avg('vote'), 2) }}</dt>
                                            <dd class="fs-sm fw-medium text-white mb-0">{{ __('Ortalama') }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <livewire:ai.answer-vote-table answerAiId="{{ $answerAI->id }}"/>
                    @break
                    @case('edit')
                        @livewire('ai.answer-ai-form', ['answerAI' => $answerAI])
                    @break
                @endswitch
            </div>
        </div>

    </div>
@endsection
