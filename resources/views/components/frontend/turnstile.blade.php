@props(['action'])
@if(auth()->guest() && \App\Helpers\TurnstileSettings::enabled())
    <div class="col-12 mb-3">
        <div wire:ignore
             x-data="eduanTurnstile($wire, @js($action), @js(\App\Helpers\TurnstileSettings::siteKey()), @js(app()->getLocale()))"
             x-on:turnstile-reset.window="if ($event.detail.instanceId === $wire.$id) reset()">
            <div x-ref="widget"></div>
            <p x-cloak x-show="failed" class="small text-danger mt-2" role="status">{{ __('turnstile.unavailable') }}</p>
            <button x-cloak x-show="failed" x-on:click="retry()" type="button" class="btn btn-outline-secondary btn-sm mt-1"><i class="fa fa-rotate-right me-1" aria-hidden="true"></i>{{ __('turnstile.retry') }}</button>
        </div>
        @error('turnstileToken')<p class="small text-danger fw-bold mt-2" role="alert">{{ $message }}</p>@enderror
    </div>
@endif
