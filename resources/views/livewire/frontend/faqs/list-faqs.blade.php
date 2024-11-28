<div>
    <div class="accordion" id="faqs">
        @foreach($this->faqs() as $faq)
            <div class="accordion-item mb-3">
                <h2 class="accordion-header" id="heading{{ $faq->id }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $faq->id }}" aria-expanded="false" aria-controls="collapse{{ $faq->id }}">
                        {{ $faq->title }}
                    </button>
                </h2>
                <div id="collapse{{ $faq->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $faq->id }}" data-bs-parent="#faqs">
                    <div class="accordion-body">
                        {!! html_entity_decode($faq->content) !!}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
