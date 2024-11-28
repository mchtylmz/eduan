@if ($paginator->hasPages())
<div class="row align-items-center justify-content-between">
    <div class="col-sm-3 d-none">
        <p class="text-muted fw-bold mb-0">
            {!! __('Showing') !!}
            <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
            {!! __('to') !!}
            <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
            /
            <span class="fw-semibold">{{ $paginator->total() }}</span>
            {!! __('results') !!}
        </p>
    </div>
    <div class="col-sm-12">
        <div class="pagination-area mt-20 mb-30">
            <ul class="flex-wrap flex-sm-wrap-reverse justify-content-center justify-content-sm-center">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li>
                        <a href="javascript:void(0);" rel="next" aria-label="@lang('pagination.next')" class="disabled">
                            <i class="fa-light fa-angle-left"></i>
                        </a>
                    </li>
                @else
                    <li>
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">
                            <i class="fa-light fa-angle-left"></i>
                        </a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li aria-disabled="true"><a>{{ $element }}</a></li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li aria-current="page"><a class="active">{{ $page }}</a></li>
                            @else
                                <li><a href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li>
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">
                            <i class="fa-light fa-angle-right"></i>
                        </a>
                    </li>
                @else
                    <li>
                        <a href="javascript:void(0);" rel="next" aria-label="@lang('pagination.next')" class="disabled">
                            <i class="fa-light fa-angle-right"></i>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
@endif
