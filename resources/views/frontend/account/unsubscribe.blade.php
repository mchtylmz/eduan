@extends('frontend.layouts.app')
@section('content')

    <!-- lessons area start -->
    <section class="h10_category-area pt-40 pb-50 bg-white">
        <div class="container">
            <div class="alert alert-info py-4 px-5 fw-semibold d-flex align-items-center">
                <i class="fa fa-fw fa-2x fa-exclamation-circle mx-1"></i>
                <h6>{{ __('Bilgilendirme aboneliğinden çıktınız, bilgilendirme abonelğine dilediğiniz zaman yeniden kayıt olabilirsiniz!') }}</h6>
            </div>
        </div>
    </section>
    <!-- lessons area end -->

@endsection
