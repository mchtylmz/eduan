<div>
    <form wire:submit.prevent="save" novalidate class="contact-form">
        @csrf
        <div class="row">
            <div class="col-12 mb-10">
                <div class="contact-form-input contact-form-textarea">
                    <textarea wire:model="message" cols="30" rows="15" placeholder="{{ __('Sorunuz veya Değerlendirmeleriniz') }}..."></textarea>
                    <span class="inner-icon"><i class="fa-thin fa-pen"></i></span>
                </div>
                @error('message')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
            <div class="col-12">
                <div class="contact-form-submit justify-content-end mb-20">
                    <div class="contact-form-btn">
                        <button type="submit" class="theme-btn contact-btn" wire:loading.attr="disabled">
                            <div wire:loading.remove>
                                <i class="fa fa-paper-plane mx-2 fa-faw"></i> {{ __('Gönder') }}
                            </div>
                            <div wire:loading>
                                <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>


    <div class="mt-3">
        @if(count($reviews = $this->reviews()))
            <div class="blog_details-comment pb-0">
                @foreach($reviews as $review)
                    <div class="blog_details-comment-item">
                        <div class="blog_details-comment-content">
                            <h6>{{ $review->user->name }} {{ str($review->user->name)->substr(0, 1) }}.</h6>
                            <span>{{ dateFormat($review->created_at, 'd M Y, H:i') }}</span>
                            <div>
                                @if($review->reply)
                                    <medium class="text-muted d-inline-flex mb-0 border rounded-2 px-1 bg-light"
                                            style="font-size: 15px;">
                                        @ {{ $review->user->name }} {{ str($review->user->name)->substr(0, 1) }}.
                                    </medium>
                                @endif
                                <p class="mb-0">
                                    {{ $review->comment }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-2">
                {{ $reviews->links('components.frontend.livewire-pagination') }}
            </div>
        @else
            <div class="alert alert-warning d-flex align-items-center">
                <i class="fa fa-exclamation-circle mx-2"></i>
                <strong class="mx-2">{{ __('Gösterilecek sonuç bulunamadı!') }}</strong>
            </div>
        @endif
    </div>
</div>
