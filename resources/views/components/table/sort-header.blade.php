@props([
    'name' => $name ?? '',
    'column' => $column ?? 'id',
    'order' => request('column') == $column && request('order') == 'ASC' ? 'DESC' : 'ASC',
    'th' => boolval($th ?? true)
])
@if($th) <th class="pt-3" style="min-width: 100px"> @endif
<a href="{{ request()->fullUrlWithQuery(['column' => $column, 'order' => $order]) }}">
    {{ $name }}
    @if(request('column') == $column && request('order') == 'ASC') <i class="fa fa-sort-asc"></i>
    @elseif(request('column') == $column && request('order') == 'DESC') <i class="fa fa-sort-desc"></i>
    @else <i class="fa fa-sort"></i>
    @endif
</a>
@if($th) </th> @endif
