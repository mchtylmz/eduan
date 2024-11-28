<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                font-weight: 100;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 36px;
                padding: 20px;
            }
        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            <div class="content">
                <div class="title">
                    <!-- Error 404 -->
                    <div class="error-404">
                        <div class="row">
                            <div class="col-md-8 offset-md-2">
                                <figure class="error-404__figure">
                                    <img src="{{ asset('assets/images/icon-ghost.svg') }}" alt="Error">
                                </figure>
                                <header class="error__header">
                                    <h2 class="error__title">{{ __('OOOOPS! Sayfa Bulunamadı!') }}</h2>
                                </header>
                                <div class="error__description">
                                    {!! __('Aradığınız sayfa taşınmış veya artık mevcut değil, isterseniz ana sayfamıza dönebilirsiniz. <br>Sorun devam ederse lütfen bizimle iletişime geçebilirsiniz.') !!}
                                </div>
                                <footer class="error__cta">
                                    <a href="/" class="btn btn-primary">{{ __("Anasayfa'ya Git") }}</a>
                                </footer>
                            </div>
                        </div>
                    </div>
                    <!-- Error 404 / End -->
                </div>
            </div>
        </div>
    </body>
</html>
