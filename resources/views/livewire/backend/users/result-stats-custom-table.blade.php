<div>

    <form wire:submit="filter">
        <div class="row align-items-end">

            @if(!$user->exists)
                <div class="col-lg-3 mb-3" wire:ignore>
                    <label class="form-label" for="userId">{{ __('Kullanıcılar') }}</label>
                    <select id="userId"
                            class="form-control selectpicker"
                            data-live-search="true"
                            data-size="10"
                            wire:model="userId">
                        <option value="" hidden>{{ __('Seçiniz') }}</option>
                        @foreach(data()->filters()->users() as $id => $name)
                            <option value="{{ $id }}">{{ $name  }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-lg-3 mb-3" wire:ignore>
                <label class="form-label" for="resultType">{{ __('Sonuç Türü') }}</label>
                <select id="resultType"
                        class="form-control selectpicker"
                        data-live-search="true"
                        data-size="10"
                        wire:model.live="resultType">
                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                    <option value="topics">{{ __('Konu') }}</option>
                    <option value="exams">{{ __('Test') }}</option>
                    <option value="tests">{{ __('Sınav') }}</option>
                </select>
            </div>

            <div class="col-lg-3 mb-3" wire:ignore>
                <label class="form-label" for="resultPercent">{{ __('Sonuç Kriteri') }}</label>
                <select id="resultPercent"
                        class="form-control selectpicker"
                        data-live-search="true"
                        data-size="10"
                        wire:model="resultPercent">
                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                    @for($i = 10; $i <= 100; $i+=10)
                        <option value="{{ $i }}">%{{ $i }} {{ __('ve altında') }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-lg-3 mb-3">
                <button type="submit" class="btn btn-alt-primary px-4 mt-3" wire:loading.attr="disabled">
                    <div wire:loading.remove>
                        <i class="fa fa-fw fa-filter me-1 opacity-50"></i> {{ __('Filtrele') }}
                    </div>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                    </div>
                </button>
            </div>

            @if($resultType == 'topics')
                <div class="col-lg-6 mb-3" wire:ignore>
                    <label class="form-label" for="topicId">{{ __('Konu') }}</label>
                    <select id="topicId"
                            class="form-control selectpicker"
                            data-live-search="true"
                            data-size="10"
                            wire:model="topicId">
                        <option value="" hidden>{{ __('Seçiniz') }}</option>
                        @foreach($this->topics() as $topic)
                            <option value="{{ $topic->id }}">{{ $topic->title  }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

        </div>
    </form>

    <hr>
    @if($showResults)

        @if($resultType == 'topics' && !empty($this->topicId))
            <div class="table-responsive">
                <div class="text-start">
                    <button type="button" class="btn btn-success px-4 mb-3" wire:click="exportTopics"
                            wire:loading.attr="disabled">
                        <div wire:loading.remove>
                            <i class="fa fa-fw fa-file-excel me-1"></i> {{ __('Excele Aktar') }}
                        </div>
                        <div wire:loading>
                            <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                        </div>
                    </button>
                    <button type="button" class="btn btn-info px-4 mb-3"
                            wire:click="showSendEmails"
                            wire:loading.attr="disabled">
                        <i class="fa fa-fw fa-at me-1"></i> {{ __('E-posta Gönder') }}
                    </button>
                </div>

                <table class="table table-responsive table-striped table-bordered w-100">
                    <thead>
                    <tr>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('İsim') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Soyisim') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Konu Adı') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Toplam Soru') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Doğru Yanıt') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Yanlış Yanıt') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Başarı Yüzdesi') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(count($results = $this->filterTopics()))
                        @foreach($results as $result)
                            <tr>
                                <td class="text-start" scope="col">{{ $result->name }}</td>
                                <td class="text-start" scope="col">{{ $result->surname }}</td>
                                <td class="text-start" scope="col">
                                    @if(str($result->topic_title)->isJson())
                                        {{ json_decode($result->topic_title, true)[app()->getLocale()] ?? '-' }}
                                    @else
                                        {{ $result->topic_title }}
                                    @endif
                                </td>
                                <td class="text-center" scope="col">{{ $result->total_questions }}</td>
                                <td class="text-center" scope="col">{{ $result->count_correct }}</td>
                                <td class="text-center" scope="col">{{ $result->count_incorrect }}</td>
                                <td class="text-center" scope="col">%{{ $result->success_rate }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="8" scope="col">{{ __('Gösterilecek sonuç bulunmuyor!') }}</td>
                        </tr>
                    @endif

                    </tbody>
                </table>

                <div>{{ $results->links() }}</div>
            </div>
        @endif

        @if($resultType == 'exams')
            <div class="table-responsive">
                <div class="text-start">
                    <button type="button" class="btn btn-success px-4 mb-3" wire:click="exportExams"
                            wire:loading.attr="disabled">
                        <div wire:loading.remove>
                            <i class="fa fa-fw fa-file-excel me-1"></i> {{ __('Excele Aktar') }}
                        </div>
                        <div wire:loading>
                            <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                        </div>
                    </button>
                    <button type="button" class="btn btn-info px-4 mb-3"
                            wire:click="showSendEmails"
                            wire:loading.attr="disabled">
                        <i class="fa fa-fw fa-at me-1"></i> {{ __('E-posta Gönder') }}
                    </button>
                </div>

                <table class="table table-responsive table-striped table-bordered w-100">
                    <thead>
                    <tr>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('İsim') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Soyisim') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Sınav Adı') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Toplam Soru') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Doğru Yanıt') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Yanlış Yanıt') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Başarı Yüzdesi') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(count($results = $this->filterExams()))
                        @foreach($results as $result)
                            <tr>
                                <td class="text-start" scope="col">{{ $result->name }}</td>
                                <td class="text-start" scope="col">{{ $result->surname }}</td>
                                <td class="text-start" scope="col">{{ $result->exam_name }}</td>
                                <td class="text-center" scope="col">{{ $result->total_questions }}</td>
                                <td class="text-center" scope="col">{{ $result->count_correct }}</td>
                                <td class="text-center" scope="col">{{ $result->count_incorrect }}</td>
                                <td class="text-center" scope="col">%{{ $result->success_rate }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="8" scope="col">{{ __('Gösterilecek sonuç bulunmuyor!') }}</td>
                        </tr>
                    @endif

                    </tbody>
                </table>

                <div>{{ $results->links() }}</div>
            </div>
        @endif

        @if($resultType == 'tests')
            <div class="table-responsive">
                <div class="text-start">
                    <button type="button" class="btn btn-success px-4 mb-3" wire:click="exportTests"
                            wire:loading.attr="disabled">
                        <div wire:loading.remove>
                            <i class="fa fa-fw fa-file-excel me-1"></i> {{ __('Excele Aktar') }}
                        </div>
                        <div wire:loading>
                            <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                        </div>
                    </button>
                    <button type="button" class="btn btn-info px-4 mb-3"
                            wire:click="showSendEmails"
                            wire:loading.attr="disabled">
                        <i class="fa fa-fw fa-at me-1"></i> {{ __('E-posta Gönder') }}
                    </button>
                </div>

                <table class="table table-responsive table-striped table-bordered w-100">
                    <thead>
                    <tr>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('İsim') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Soyisim') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Test Adı') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Puan') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Toplam Soru') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Doğru Yanıt') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Yanlış Yanıt') }}</th>
                        <th scope="col" class="bg-secondary text-white text-center">{{ __('Başarı Yüzdesi') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(count($results = $this->filterTests()))
                        @foreach($results as $result)
                            <tr>
                                <td class="text-start" scope="col">{{ $result->name }}</td>
                                <td class="text-start" scope="col">{{ $result->surname }}</td>
                                <td class="text-start" scope="col">{{ $result->test_name }}</td>
                                <td class="text-start" scope="col">{{ $result->point }}</td>
                                <td class="text-center" scope="col">{{ $result->total_questions }}</td>
                                <td class="text-center" scope="col">{{ $result->count_correct }}</td>
                                <td class="text-center" scope="col">{{ $result->count_incorrect }}</td>
                                <td class="text-center" scope="col">%{{ $result->success_rate }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="8" scope="col">{{ __('Gösterilecek sonuç bulunmuyor!') }}</td>
                        </tr>
                    @endif

                    </tbody>
                </table>

                <div>{{ $results->links() }}</div>
            </div>
        @endif

    @endif

</div>
