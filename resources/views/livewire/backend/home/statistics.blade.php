<div>
    @if(!empty($data))
        <a class="block block-rounded block-link-pop mb-3" href="{{ $data['footerRoute'] ?? 'javascript:void(0)' }}">
            <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                <div>
                    <i class="{{ $data['icon'] }} fs-3 text-muted"></i>
                </div>
                <dl class="ms-3 text-end mb-0">
                    <dt class="h3 fw-extrabold mb-0">{{ $data['count'] ?? 0 }}</dt>
                    <dd class="fs-sm fw-medium text-muted mb-0">{{ $data['description'] ?? '' }}</dd>
                </dl>
            </div>
        </a>
    @endif
</div>


