@props(['text', 'field'])
@if(strip_tags($text) !== $text)
    {{-- Preserve the existing administrator-edited HTML contract. --}}
    {!! convertLatexToImg($text, $field) !!}
@else
    @php
        $hasDelimiters = str_contains($text, '$$');
        $isBareFormula = $field === 'latex' || ($field === 'final_answer' && preg_match('/\\\\[a-zA-Z]+/', $text));
        $parts = $hasDelimiters ? preg_split('/(\$\$.*?\$\$)/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) : [$text];
    @endphp
    @foreach($parts as $part)
        @if(str_starts_with($part, '$$') && str_ends_with($part, '$$'))
            <x-ai.formula :formula="substr($part, 2, -2)" />
        @elseif(!$hasDelimiters && $isBareFormula)
            <x-ai.formula :formula="$part" />
        @else
            {{ $part }}
        @endif
    @endforeach
@endif
