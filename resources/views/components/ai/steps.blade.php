<div class="answer-ai">
    @if(($answerAi->content['status'] ?? '') === 'ready')
        @foreach($answerAi->content['steps'] ?? [] as $step)
            <div class="mb-3">
                @if($step['rule'] ?? '')<h6 class="fw-bold mb-1">{{ $step['rule'] }}</h6>@endif
                @if($step['definition'] ?? '')<p class="mb-1">{{ $step['definition'] }}</p>@endif
                <p class="mb-1">{{ $step['explanation'] ?? '' }}</p>
                @if($step['latex'] ?? '')
                    <x-ai.formula :formula="$step['latex']" />
                @endif
            </div>
        @endforeach
        <h6 class="fw-bold">{{ __('Final Answer:') }} {{ $answerAi->content['final_answer'] ?? '' }}</h6>
        @if($answerAi->content['final_answer_latex'] ?? '')
            <x-ai.formula :formula="$answerAi->content['final_answer_latex']" />
        @endif
    @else
        @if($steps = $answerAi->content['steps'] ?? [])
            @foreach($steps as $step)
                <div>
                    @if($definition = $step['definition'] ?? '')
                        <div class="fw-bold mb-1 latex-source" id="def-{{ $answerAi->id }}-{{ $loop->iteration }}">
                            <x-ai.legacy-text :text="$definition" field="definition" />
                        </div>
                    @endif
                    @if($explanation = $step['explanation'] ?? '')
                        <div class="fw-medium mb-1 latex-source" id="exp-{{ $answerAi->id }}-{{ $loop->iteration }}">
                            <x-ai.legacy-text :text="$explanation" field="explanation" />
                        </div>
                    @endif
                    @if($latex = $step['latex'] ?? '')
                        <div class="fw-medium mb-3 latex-source" id="latex-{{ $answerAi->id }}-{{ $loop->iteration }}">
                            <x-ai.legacy-text :text="$latex" field="latex" />
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
        @if($final_answer = $answerAi->content['final_answer'] ?? '')
            <div class="fw-bold">
                {{ __('Final Answer:') }}
                <div id="final-{{ $answerAi->id }}"><x-ai.legacy-text :text="$final_answer" field="final_answer" /></div>
            </div>
        @endif
    @endif
</div>
