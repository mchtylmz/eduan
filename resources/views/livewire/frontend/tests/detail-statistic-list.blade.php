<div>
    @php
            $resultStats = auth()->check() ? examResultStats(exam: $exam, user: auth()->user()) : [];

            $results_count = $resultStats['results_count'] ?? 0;
            $question_count = $resultStats['question_count'] ?? 0;
            $correct_count = $resultStats['correct_count'] ?? 0;
            $incorrect_count = $resultStats['incorrect_count'] ?? 0;
    @endphp
    @if($results_count)

    <div class="course_details-sidebar mb-3 pt-0 pb-0">
        <div class="course_details-list">
            <ul class="mb-0">
                <li class="text-success">
                    <span><i class="fa fa-percent text-success"></i>{{ __('account.success') }}</span>
                    <span>{{ $question_count ? intval(($correct_count * 100) / $question_count) : 0 }}%</span>
                </li>
                <li class="text-success">
                    <span>
                        <i class="fa fa-percent text-success"></i> {{ $results_count }} {{ __('learning.result') }}
                    </span>
                    <span>
                       {{ $correct_count }} {{ __('account.correct') }} / {{ $incorrect_count }} {{ __('account.incorrect') }}
                    </span>
                </li>
                <li class="">
                    <a href="{{ route('frontend.test.solutions', $exam->code) }}" class="course-btn theme-btn bg-success">
                        {{ __('learning.view_results') }}
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
                    <span><i class="fa-thin fa-language"></i>{{ __('learning.language') }}</span>
                    <span>{{ $exam->language->name ?? '' }}</span>
                </li>
                @foreach($this->lessons() as $item)
                <li>
                    <span>
                        <i class="fa-thin fa-pen-clip"></i>
                        {{ $item->lesson->name ?? '' }}
                    </span>
                    <span class="text-nowrap">
                        {{ $item->questions_count ?? 0 }} {{ __('learning.questions') }}
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
                        {{ $item->questions_count ?? 0 }} {{ __('learning.questions') }}
                    </span>
                </li>
                @endforeach
                <li>
                    <span><i class="fa-thin fa-file-lines"></i>{{ __('learning.total_questions') }}</span>
                    <span>{{ $this->summary()['questions_count'] }}</span>
                </li>
                <li>
                    <span><i class="fa-thin fa-clock"></i>{{ __('learning.duration') }}</span>
                    <span>∼ {{ intval($this->summary()['seconds'] / 60) }} {{ __('learning.minutes') }}</span>
                </li>
                <li>
                    <span><i class="fa-thin fa-eye"></i>{{ __('learning.visibility') }}</span>
                    <span>{{ \App\Enums\VisibilityEnum::PREMIUM->is($exam->visibility) ? __('account.premium_only') : __('account.members_only') }}</span>
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
                        @if($results_count)
                            <a href="{{ route('frontend.test.start', $exam->code) }}" class="course-btn theme-btn theme-btn-big">
                                {{ __('learning.retry') }}
                            </a>
                        @else
                            <a href="{{ route('frontend.test.start', $exam->code) }}" class="course-btn theme-btn theme-btn-big">
                                {{ __('learning.solve') }}
                            </a>
                        @endif
                    @else
                        <a href="javascript:void(0)" class="course-btn theme-btn theme-btn-big" data-bs-toggle="modal"
                           data-bs-target="#informationModal">
                            {{ __('learning.solve') }}
                        </a>
                    @endif
                @else
                    <a href="javascript:void(0)" class="course-btn theme-btn theme-btn-big" data-bs-toggle="modal"
                       data-bs-target="#informationModal">
                        {{ __('learning.solve') }}
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
