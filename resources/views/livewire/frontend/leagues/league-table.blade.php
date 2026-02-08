<div>
    <div class="card border mb-3">
        <div class="card-header border-0 py-2">
            <!-- league-header -->
            <div class="row align-items-center justify-content-between">
                <!-- col-lg-8 -->
                <div class="col-lg-8 order-2 order-sm-1">
                    <h5 class="card-title mt-3 mt-sm-0 mb-1 mb-sm-0">
                        @if($selectedLeague)
                            <span>{{ dateFormat($selectedLeague->start_at, 'd M Y, l') }}</span>
                            -
                            <span>{{ dateFormat($selectedLeague->end_at, 'd M Y, l') }}</span>
                        @else
                            <span>{{ __('Lig Sonuçları') }}</span>
                        @endif
                    </h5>
                </div>
                <!-- col-lg-8 -->
                <!-- col-lg-4 -->
                <div class="col-lg-4 order-1 order-sm-2 d-flex align-items-center">
                    <i class="fa fw-medium fa-spinner fa-pulse fa-2x mx-1 choose-league-loading d-none"></i>
                    <div class="btn-group w-100 border border-2">
                        <button type="button"
                                class="py-2 btn btn-default dropdown-toggle d-flex align-items-center justify-content-between"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <strong>{{ __('Geçmiş Lig Sonuçları') }}</strong>
                        </button>
                        <ul class="dropdown-menu w-100">
                            @if(count($leagues = $this->leagues()))
                                @foreach($leagues as $league)
                                    <li>
                                        <a class="dropdown-item d-flex justify-content-between align-items-center choose-league"
                                           href="{{ route('frontend.leagues.detail', $league->code) }}">
                                            <div>
                                                <span>{{ dateFormat($league->start_at, 'd M Y') }}</span>
                                                -
                                                <span>{{ dateFormat($league->end_at, 'd M Y') }}</span>
                                            </div>
                                            @if($selectedLeague && $selectedLeague->id == $league->id)
                                                <i class="fa fa-check mx-1"></i>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            @else
                                <li>
                                    <p class="mb-0 p-3">{{ __('Gösterilecek sonuç bulunmuyor!') }}</p>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
                <!-- col-lg-4 -->
            </div>
            <!-- league-header -->
        </div>
    </div>
    <!-- card -->

    @if($selectedLeague)
        <div class="row">
            <div class="col-lg-4 mb-3 px-2">
                @livewire('frontend.leagues.league-result-table', [
                'league' => $selectedLeague,
                'name' => __('1. Lig'),
                'ranking' => 1
                ])
            </div>
            <div class="col-lg-4 mb-3 px-2">
                @livewire('frontend.leagues.league-result-table', [
                'league' => $selectedLeague,
                'name' => __('2. Lig'),
                'ranking' => 2
                ])
            </div>
            <div class="col-lg-4 mb-3 px-2">
                @livewire('frontend.leagues.league-result-table', [
                'league' => $selectedLeague,
                'name' => __('3. Lig'),
                'ranking' => 3
                ])
            </div>
        </div>
    @else
        <div class="alert alert-danger py-3">
            <strong>{{ __('Gösterilecek sonuç bulunmuyor!') }}</strong>
        </div>
    @endif

</div>
