<div>
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">{{ __('En Çok Görünütülenen Dersler') }}</h3>
        </div>
        <div class="block-content p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter mb-0">
                    <thead>
                    <tr>
                        <th>{{ __('Adı') }}</th>
                        <th class="text-center">{{ __('Görüntüleme') }}</th>
                        <th class="text-center"><i class="fa fa-eye fa-fw text-dark"></i></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($this->lessons() as $lesson)
                        <tr>
                            <td class="fs-sm">{{ $lesson->name }}</td>
                            <td class="fs-sm text-center">{{ $lesson->hits }}</td>
                            <td class="fs-sm text-center">
                                <a href="">
                                    <i class="fa fa-external-link fa-fw text-dark"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
