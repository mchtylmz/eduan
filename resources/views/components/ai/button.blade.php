@props([
    'questionCode' => $questionCode ?? '0'
])
<div class="row justify-content-center mt-3">
    <div class="col-lg-5">
        <a target="_blank" href="{{ route('frontend.ai.solution', $questionCode) }}" class="btn btn-success py-2 px-2 w-100 fw-semibold text-white">
            <i class="fa fa-fw fa-wand-magic-sparkles me-1"></i>
            <span>{{ __('Yapay Zeka ile Çözümü Yeniden Anlat') }}</span>
        </a>
    </div>
</div>
