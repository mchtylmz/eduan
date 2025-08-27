<div class="answer-ai">
    @if($steps = $answerAi->content['steps'] ?? [])
        @foreach($steps as $step)
            <div>
                @if($definition = $step['definition'] ?? '')
                    <h6 class="fw-bold mb-1 latex-source" id="def-{{ $answerAi->id }}-{{ $loop->iteration }}">
                        {!! convertLatexToImg($definition, 'definition') !!}
                    </h6>
                @endif
                @if($explanation = $step['explanation'] ?? '')
                    <h6 class="fw-medium mb-1 latex-source" id="exp-{{ $answerAi->id }}-{{ $loop->iteration }}">
                        {!! convertLatexToImg($explanation, 'explanation') !!}
                    </h6>
                @endif
                @if($latex = $step['latex'] ?? '')
                    <p class="fw-medium mb-3 latex-source" id="latex-{{ $answerAi->id }}-{{ $loop->iteration }}">
                        {!! convertLatexToImg($latex, 'latex') !!}
                    </p>
                @endif
            </div>
        @endforeach
    @endif
    @if($final_answer = $answerAi->content['final_answer'] ?? '')
        <h6 class="fw-bold">
            {{ __('Final Answer:') }}
            <span class="latex-source11" id="final-{{ $answerAi->id }}"> {!! convertLatexToImg($final_answer, 'final_answer') !!}</span>
        </h6>
    @endif
</div>
