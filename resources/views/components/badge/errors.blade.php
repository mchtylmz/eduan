@if($errors->any())
    <div class="mb-3 row">
        @foreach ($errors->all() as $error)
            <div class="col-lg-6 mb-1">
                <p class="bg-danger text-white text-sm my-0 px-3 py-1 fw-medium">{{ $error ?? '' }}</p>
            </div>
        @endforeach
    </div>
@endif
