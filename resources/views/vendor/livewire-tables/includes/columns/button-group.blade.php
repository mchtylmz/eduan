@if(count($attributes))
    @php $attributes['class'] .= ' dropdown-toggle'; @endphp
@endif
<div {!! count($attributes) ? $column->arrayToAttributes($attributes) : '' !!}>
        <div class="dropdown">
            <button type="button"
                    {!! count($attributes) ? $column->arrayToAttributes($attributes) : '' !!}
                    class="btn btn-sm btn-alt-secondary dropdown-toggle"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false">
                {{ __('İşlemler') }}
            </button>
            <div class="dropdown-menu fs-sm">
                @foreach($buttons as $button)
                    <li>{!! $button->getContents($row) !!}</li>
                @endforeach
            </div>
        </div>
</div>
