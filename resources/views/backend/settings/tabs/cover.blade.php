<!-- form -->
<form class="js-validation" action="{{ route('admin.settings.store') }}" method="POST"
      enctype="multipart/form-data">
    <!-- row -->
   <div class="row">
       <div class="col-lg-6 mb-3">
           <label class="form-label" for="coverAuth">{{ __('Giriş / Kayıt') }}</label>
           <input type="file" class="dropify" id="coverAuth" name="images[coverAuth]"
                  data-show-remove="false"
                  data-show-errors="true"
                  data-allowed-file-extensions="jpg png jpeg webp"
                  accept=".jpg,.png,.jpeg,.webp"
                  data-max-file-size="10M"
                  @if($image = settings()->coverAuth) data-default-file="{{ asset($image) }}" @endif
           />
       </div>
       <div class="col-lg-6 mb-3">
           <label class="form-label" for="coverBlog">{{ __('Blog') }}</label>
           <input type="file" class="dropify" id="coverBlog" name="images[coverBlog]"
                  data-show-remove="false"
                  data-show-errors="true"
                  data-allowed-file-extensions="jpg png jpeg webp"
                  accept=".jpg,.png,.jpeg,.webp"
                  data-max-file-size="10M"
                  @if($image = settings()->coverBlog) data-default-file="{{ asset($image) }}" @endif
           />
       </div>
       <div class="col-lg-6 mb-3">
           <label class="form-label" for="coverContact">{{ __('İletişim') }}</label>
           <input type="file" class="dropify" id="coverContact" name="images[coverContact]"
                  data-show-remove="false"
                  data-show-errors="true"
                  data-allowed-file-extensions="jpg png jpeg webp"
                  accept=".jpg,.png,.jpeg,.webp"
                  data-max-file-size="10M"
                  @if($image = settings()->coverContact) data-default-file="{{ asset($image) }}" @endif
           />
       </div>
       <div class="col-lg-6 mb-3">
           <label class="form-label" for="coverLessons">{{ __('Dersler') }}</label>
           <input type="file" class="dropify" id="coverLessons" name="images[coverLessons]"
                  data-show-remove="false"
                  data-show-errors="true"
                  data-allowed-file-extensions="jpg png jpeg webp"
                  accept=".jpg,.png,.jpeg,.webp"
                  data-max-file-size="10M"
                  @if($image = settings()->coverLessons) data-default-file="{{ asset($image) }}" @endif
           />
       </div>
       <div class="col-lg-6 mb-3">
           <label class="form-label" for="coverTopics">{{ __('Konular') }}</label>
           <input type="file" class="dropify" id="coverTopics" name="images[coverTopics]"
                  data-show-remove="false"
                  data-show-errors="true"
                  data-allowed-file-extensions="jpg png jpeg webp"
                  accept=".jpg,.png,.jpeg,.webp"
                  data-max-file-size="10M"
                  @if($image = settings()->coverTopics) data-default-file="{{ asset($image) }}" @endif
           />
       </div>
       <div class="col-lg-6 mb-3">
           <label class="form-label" for="coverTests">{{ __('Testler') }}</label>
           <input type="file" class="dropify" id="coverTests" name="images[coverTests]"
                  data-show-remove="false"
                  data-show-errors="true"
                  data-allowed-file-extensions="jpg png jpeg webp"
                  accept=".jpg,.png,.jpeg,.webp"
                  data-max-file-size="10M"
                  @if($image = settings()->coverTests) data-default-file="{{ asset($image) }}" @endif
           />
       </div>

       <div class="col-lg-6 mb-3">
           <label class="form-label" for="coverAccount">{{ __('Hesabım') }}</label>
           <input type="file" class="dropify" id="coverTests" name="images[coverAccount]"
                  data-show-remove="false"
                  data-show-errors="true"
                  data-allowed-file-extensions="jpg png jpeg webp"
                  accept=".jpg,.png,.jpeg,.webp"
                  data-max-file-size="10M"
                  @if($image = settings()->coverAccount) data-default-file="{{ asset($image) }}" @endif
           />
       </div>

       <div class="col-lg-6 mb-3">
           <label class="form-label" for="coverFaq">{{ __('Sıkça Sorulan Sorular') }}</label>
           <input type="file" class="dropify" id="coverFaq" name="images[coverFaq]"
                  data-show-remove="false"
                  data-show-errors="true"
                  data-allowed-file-extensions="jpg png jpeg webp"
                  accept=".jpg,.png,.jpeg,.webp"
                  data-max-file-size="10M"
                  @if($image = settings()->coverFaq) data-default-file="{{ asset($image) }}" @endif
           />
       </div>

       <div class="col-lg-6 mb-3">
           <label class="form-label" for="coverPage">{{ __('Diğer Sayfalar Detayı') }}</label>
           <input type="file" class="dropify" id="coverFaq" name="images[coverPage]"
                  data-show-remove="false"
                  data-show-errors="true"
                  data-allowed-file-extensions="jpg png jpeg webp"
                  accept=".jpg,.png,.jpeg,.webp"
                  data-max-file-size="10M"
                  @if($image = settings()->coverPage) data-default-file="{{ asset($image) }}" @endif
           />
       </div>
   </div>

    @can('settings:update')
        <div class="mb-3 text-center py-2 mt-3">
            <button type="submit" class="btn btn-alt-primary px-4">
                <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Kaydet') }}
            </button>
        </div>
    @endcan
</form>
<!-- form -->
