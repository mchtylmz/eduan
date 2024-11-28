@php
    if (! isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? <<<JS
           (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
        JS
        : '';
@endphp
<div>
    @if ($paginator->hasPages())
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-12">
                <div class="pagination-area mt-20 mb-30 d-flex justify-content-center flex-fill d-sm-none">
                    <ul class="pagination">
                        {{-- Previous Page Link --}}
                        @if ($paginator->onFirstPage())
                            <li aria-disabled="true" style="min-width: 150px">
                                <a class="active" disabled>@lang('pagination.previous')</a>
                            </li>
                        @else
                            <li>
                                <a type="button" style="min-width: 150px" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled">@lang('pagination.previous')</a>
                            </li>
                        @endif

                        {{-- Next Page Link --}}
                        @if ($paginator->hasMorePages())
                            <li>
                                <a type="button" style="min-width: 150px" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" class="page-link" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled">@lang('pagination.next')</a>
                            </li>
                        @else
                            <li aria-disabled="true">
                                <a aria-hidden="true" style="min-width: 150px">@lang('pagination.next')</a>
                            </li>
                        @endif
                    </ul>
                </div>

                <div class="pagination-area mt-20 mb-30 d-none flex-sm-fill d-sm-flex align-items-sm-center justify-content-sm-center">
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
                                <a type="button" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" class="page-link" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" rel="next" aria-label="@lang('pagination.next')">
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
                                        <li wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}" aria-current="page"><a class="active">{{ $page }}</a></li>
                                    @else
                                        <li wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}"><a type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}">{{ $page }}</a></li>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($paginator->hasMorePages())
                            <li>
                                <a type="button" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" class="page-link" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" rel="next" aria-label="@lang('pagination.next')">
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

</div>
