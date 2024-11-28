<div class="solve-right-sticky">
    <div class="course_details-sidebar mt-0 mb-0 p-0">
        <div class="course_details-price mt-0 mb-0 py-3 d-flex align-items-center justify-content-between">
            <h5 class="mb-0">{{ __('Test Soruları') }}</h5>
            <div id="elapsed-time"></div>
        </div>
        <div class="course_details-list">
            <ul class="mb-3">
                @if($questionTimes = $this->questionTimes())
                    @foreach($questionTimes as $questionTime)
                        <li>
                            <span><i class="fa-thin fa-question-circle"></i>{{ __('Soru') }} {{ $loop->iteration }}</span>
                            <span>∼ {{ intval($questionTime->time / 60) }} {{ __('dakika') }}</span>
                        </li>
                    @endforeach
                    <li>
                        <span><i class="fa-thin fa-question-circle"></i>{{ __('Toplam') }}</span>
                        <span>∼ {{ intval(collect($questionTimes)->sum('time') / 60) }} {{ __('dakika') }}</span>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
