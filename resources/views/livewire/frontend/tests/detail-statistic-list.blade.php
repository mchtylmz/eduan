<div>
    @if(count($userResultsWithoutGroupBy = $exam->userResultsWithoutGroupBy))
        @php
            $results_count = collect($userResultsWithoutGroupBy)->count();
            $question_count = collect($userResultsWithoutGroupBy)->sum('question_count');
            $correct_count = collect($userResultsWithoutGroupBy)->sum('correct_count');
            $incorrect_count = collect($userResultsWithoutGroupBy)->sum('incorrect_count');
        @endphp
    <div class="course_details-sidebar mb-3 pt-0 pb-0">
        <div class="course_details-list">
            <ul class="mb-0">
                <li class="text-success">
                    <span><i class="fa fa-percent text-success"></i>{{ __('Başarı') }}</span>
                    <span>%{{ $correct_count ? intval(($correct_count * 100) / $question_count) : 0 }}</span>
                </li>
                <li class="text-success">
                    <span>
                        <i class="fa fa-percent text-success"></i> {{ $results_count }} {{ __('Sonuç') }}
                    </span>
                    <span>
                       {{ $correct_count }} {{ __('Doğru') }} / {{ $incorrect_count }} {{ __('Yanlış') }}
                    </span>
                </li>
                <li class="">
                    <a href="{{ route('frontend.test.solutions', $exam->code) }}" class="course-btn theme-btn bg-success">
                        {{ __('Sonuçlarımı Görüntüle') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
    @endif

    <div class="course_details-sidebar mb20 pt-0 pb-4">
        <div class="course_details-list">
            <ul class="mb-3">
                <li>
                    <span><i class="fa-thin fa-language"></i>{{ __('Dil') }}</span>
                    <span>{{ $exam->language->name ?? '' }}</span>
                </li>
                @foreach($this->lessons() as $item)
                <li>
                    <span>
                        <i class="fa-thin fa-pen-clip"></i>
                        {{ $item->lesson->name ?? '' }}
                    </span>
                    <span class="text-nowrap">
                        {{ $item->questions_count ?? 0 }} {{ __('Soru') }}
                    </span>
                </li>
                @endforeach
                @foreach($this->topics() as $item)
                <li>
                    <span>
                        <i class="fa-thin fa-book-alt"></i>
                        {{ $item->topic->title ?? '' }}
                    </span>
                    <span class="text-nowrap">
                        {{ $item->questions_count ?? 0 }} {{ __('Soru') }}
                    </span>
                </li>
                @endforeach
                <li>
                    <span><i class="fa-thin fa-file-lines"></i>{{ __('Toplam Soru') }}</span>
                    <span>{{ $exam->questions()->active()->count() }}</span>
                </li>
                <li>
                    <span><i class="fa-thin fa-clock"></i>{{ __('Toplam Süre') }}</span>
                    <span>∼ {{ intval($exam->questions()->active()->sum('time') / 60) }} {{ __('dakika') }}</span>
                </li>
                <li>
                    <span><i class="fa-thin fa-eye"></i>{{ __('Görünüm') }}</span>
                    <span>{{ $exam->visibility->name() ?? '' }}</span>
                </li>
                {{-----
                <li>
                    <span><i class="fa-thin fa-calendar-days"></i>{{ __('Yayın Tarihi') }}</span>
                    <span>{{ dateFormat($exam->created_at, 'd M Y') }}</span>
                </li>
                ----}}
            </ul>
            <div class="course_details-sidebar-btn">
                @auth()
                    @if(\App\Enums\VisibilityEnum::LOGGED->is($exam->visibility) || auth()->user()->can('exams:solve'))
                        @if(count($exam->userResults))
                            <a href="{{ route('frontend.test.start', $exam->code) }}" class="course-btn theme-btn theme-btn-big">
                                {{ __('Testi Tekrar Çöz') }}
                            </a>
                        @else
                            <a href="{{ route('frontend.test.start', $exam->code) }}" class="course-btn theme-btn theme-btn-big">
                                {{ __('Testi Çöz') }}
                            </a>
                        @endif
                    @else
                        <a href="javascript:void(0)" class="course-btn theme-btn theme-btn-big" data-bs-toggle="modal"
                           data-bs-target="#informationModal">
                            {{ __('Testi Çöz') }}
                        </a>
                    @endcan
                @else
                    <a href="javascript:void(0)" class="course-btn theme-btn theme-btn-big" data-bs-toggle="modal"
                       data-bs-target="#informationModal">
                        {{ __('Testi Çöz') }}
                    </a>
                @endauth
            </div>
        </div>
    </div>

    @cannot('exams:solve')
        <div class="modal fade" id="informationModal" tabindex="-1" aria-labelledby="informationModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-light border-0">
                        <h5 class="modal-title" id="informationModalLabel">{{ __('Bilgilendirme') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body warning-test p-3 rounded-0 m-0">
                        <div class="text-danger alert alert-danger p-3 d-flex align-items-center">
                            @auth()
                                @cannot('exams:solve')
                                    <i class="fa-light fa-unlock-alt fa-2x fw-fw me-3"></i>
                                    <p class="text-danger fw-semibold mb-0">
                                        {!! settingLocale('examSolveNotPremiumDescription') !!}
                                    </p>
                                @endcan
                            @else
                                <i class="fa fa-exclamation-square fa-2x fw-fw me-3"></i>
                                <p class="text-danger fw-semibold mb-0">
                                    {!! settingLocale('examSolveNotAuthDescription') !!}
                                </p>
                            @endauth
                        </div>
                        @guest()
                            <div class="auth text-end">
                                <a class="btn btn-secondary header-btn theme-btn theme-btn-medium px-5"
                                   href="{{ route('login') }}">
                                    <i class="fa fa-user-plus me-0 me-sm-2"></i>
                                    <span class="d-none d-sm-block">{{ __('Oturum Aç') }}</span>
                                </a>
                            </div>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    @endcannot
</div>
