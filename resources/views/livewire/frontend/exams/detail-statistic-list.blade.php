<div>
    <div class="course_details-sidebar mb20 pt-0 pb-4">
        <div class="course_details-list">
            <ul class="mb-3">
                <li>
                    <span><i class="fa-thin fa-language"></i>{{ __('learning.language') }}</span>
                    <span>{{ $test->language->name ?? '' }}</span>
                </li>
                @foreach($this->sectionsWithQuestionsCount() as $section)
                    <li>
                    <span>
                        <i class="fa-thin fa-list-dots"></i>
                        {{ __('learning.section') }} {{ $loop->iteration }}
                    </span>
                        <span class="text-nowrap">
                        {{ $section->questions_count ?: 0 }} {{ __('learning.questions') }}
                    </span>
                    </li>
                @endforeach
                <li>
                    <span><i class="fa-thin fa-table-columns"></i>{{ __('learning.sections') }}</span>
                    <span>{{ $this->sectionsWithQuestionsCount()->count() }}</span>
                </li>
                <li>
                    <span><i class="fa-thin fa-file-lines"></i>{{ __('learning.total_questions') }}</span>
                    <span>{{ $test->questionsWithQuestion()->active()->count() }}</span>
                </li>
                <li>
                    <span><i class="fa-thin fa-clock"></i>{{ __('learning.duration') }}</span>
                    <span>
                        ∼ {{ $secondToTime = secondToTime($test->duration) }}
                        (<small class="text-muted">{{ formatSecondToTime($secondToTime) }}</small>)
                    </span>
                </li>
            </ul>
            <div class="course_details-sidebar-btn">
                @if(\App\Enums\StatusEnum::ACTIVE->is($test->status))
                    @auth()
                        @if(auth()->user()->can('tests:solve'))
                            @if($test->results()->where('user_id', auth()->id())->exists())
                                <a onclick="return confirm('{{ __('Sınav başlayacaktır, sınav süresi içerisinde bitirmelisiniz, aksi durumlarda yanıtlanmamış olarak değerlendirilir.') }}')" href="{{ route('frontend.exam.start', $test->code) }}" class="exam-start-btn course-btn theme-btn theme-btn-big">
                                    {{ __('learning.retry') }}
                                </a>
                            @else
                                <a onclick="return confirm('{{ __('Sınav başlayacaktır, sınav süresi içerisinde bitirmelisiniz, aksi durumlarda yanıtlanmamış olarak değerlendirilir.') }}')" href="{{ route('frontend.exam.start', $test->code) }}" class="exam-start-btn course-btn theme-btn theme-btn-big">
                                    {{ __('learning.start') }}
                                </a>
                            @endif
                        @else
                            <a href="javascript:void(0)" class="course-btn theme-btn theme-btn-big" data-bs-toggle="modal"
                               data-bs-target="#informationModal">
                                {{ __('learning.start') }}
                            </a>
                        @endif
                    @else
                        <a href="javascript:void(0)" class="course-btn theme-btn theme-btn-big" data-bs-toggle="modal"
                           data-bs-target="#informationModal">
                            {{ __('learning.start') }}
                        </a>
                    @endauth
                @else
                    <button type="button" class="course-btn theme-btn theme-btn-big bg-dark" disabled>
                        <i class="fa fa-hourglass-1 fa-pulse me-2"></i> {{ __('Yakında Başlayacak') }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    @cannot('tests:solve')
        <div class="modal fade" id="informationModal" tabindex="-1" aria-labelledby="informationModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-light border-0">
                        <h5 class="modal-title" id="informationModalLabel">{{ __('learning.information') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body warning-test p-3 rounded-0 m-0">
                        <div class="text-danger alert alert-danger p-3 d-flex align-items-center">
                            @auth()
                                @cannot('tests:solve')
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
