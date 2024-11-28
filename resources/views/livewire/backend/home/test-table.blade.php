<div>
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            @if($popularHits)
                <h3 class="block-title">{{ __('En Çok Görünütülenen Testler') }}</h3>
            @endif
            @if($popularResults)
                <h3 class="block-title">{{ __('En Çok Yanıtlanan Testler') }}</h3>
            @endif
        </div>
        <div class="block-content p-0">
            <div class="table-responsive">
                @if($popularHits)
                    <table class="table table-bordered table-striped table-vcenter mb-0">
                        <thead>
                        <tr>
                            <th>{{ __('Dil') }}</th>
                            <th>{{ __('Adı') }}</th>
                            <th class="text-center">{{ __('Görüntüleme') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($this->tests() as $exam)
                            <tr>
                                <td class="fs-sm">{{ $exam->language->name }}</td>
                                <td class="fs-sm">{{ $exam->name }}</td>
                                <td class="fs-sm text-center">{{ $exam->hits }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
                @if($popularResults)
                    <table class="table table-bordered table-striped table-vcenter mb-0">
                        <thead>
                        <tr>
                            <th>{{ __('Dil') }}</th>
                            <th>{{ __('Adı') }}</th>
                            <th class="text-center">{{ __('Yanıt') }}</th>
                            <th class="text-center">{{ __('D/Y') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($this->tests() as $result)
                            <tr>
                                <td class="fs-sm">{{ $result->exam->language->name }}</td>
                                <td class="fs-sm">{{ $result->exam->name }}</td>
                                <td class="fs-sm text-center">{{ $result->exams_count }}</td>
                                <td class="fs-sm text-center">
                                    {{ $result->correct_count }}/{{ $result->incorrect_count }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
