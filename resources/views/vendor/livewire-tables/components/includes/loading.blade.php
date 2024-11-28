@aware(['isTailwind', 'isBootstrap', 'tableName', 'component'])
@props(['colCount' => 1])

@php
$customAttributes['loader-wrapper'] = $this->getLoadingPlaceHolderWrapperAttributes();
$customAttributes['loader-icon'] = $this->getLoadingPlaceHolderIconAttributes();
@endphp
@if($this->hasLoadingPlaceholderBlade())
    @include($this->getLoadingPlaceHolderBlade(), ['colCount' => $colCount])
@else

    <tr wire.loading wire:key="{{ $tableName }}-loader" class="hidden d-none"
    {{
        $attributes->merge($customAttributes['loader-wrapper'])
            ->class(['w-full text-center h-screen place-items-center align-middle' => $isTailwind && ($customAttributes['loader-wrapper']['default'] ?? true)])
            ->class(['w-100 text-center h-100 align-items-center' => $isBootstrap && ($customAttributes['loader-wrapper']['default'] ?? true)])
    }}
    wire:loading.class.remove="hidden d-none"
    >
        <td colspan="{{ $colCount }}">
            <div class="h-min self-center align-middle text-center">
                <div class="my-3">
                    <i class="fa fa-spinner fa-pulse fa-fw" style="--fa-animation-duration: 0.5s;"></i>
                    {{ $this->getLoadingPlaceholderContent() }}
                </div>
            </div>
        </td>
    </tr>

@endif
