<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('subject')</title>
    <style>
        img, table, iframe, video {
            width: 100% !important;
            object-fit: contain !important;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f1f1f1;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .email-wrapper {
            background-color: #f1f1f1;
            padding: 20px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 5px;
            padding: 20px;
        }
        .email-header {
            text-align: center;
            background-color: #f1f1f1;
            padding: 10px 0;
            margin-bottom: 20px;
        }
        .email-header img {
            max-width: 150px;
            object-fit: contain !important;
        }
        .email-content {
            font-size: 16px;
            line-height: 1.5;
            text-align: left;
            font-family: Arial, sans-serif !important;
            white-space: normal !important;
        }
        .email-content * {
            font-family: Arial, sans-serif !important;
            white-space: normal !important;
            max-width: 100% !important;
            object-fit: contain !important;
        }
        .email-button {
            display: block;
            width: fit-content;
            margin: 20px auto;
            padding: 12px 20px;
            background-color: {{ settings()->primaryColor ?? '#000' }};
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
        }
        .email-footer {
            font-size: 12px;
            color: #777;
            text-align: center;
            margin-top: 20px;
            background-color: #f1f1f1;
            padding: 10px 0;
        }
        .email-footer a {
            color: {{ settings()->primaryColor ?? '#000' }};
            text-decoration: none;
        }
        .plain-link {
            word-break: break-all;
            color: {{ settings()->primaryColor ?? '#000' }};
        }
    </style>
</head>
<body>
<div class="email-wrapper">
    <!-- Logo -->
    @if($siteLogo = settings()->siteLogo)
        <div class="email-header">
            <img src="{{ asset($siteLogo) }}" alt="{{ settingLocale('siteTitle') }}" />
        </div>
    @endif
    <!-- İçerik -->
    <div class="email-container">
        <div class="email-content">
            @yield('content')
        </div>
    </div>
    <!-- Alt Bilgi -->
    <div class="email-footer">
        <p>{{ settingLocale('siteTitle') }} © {{ now()->year }} | {{ __('Tüm Hakları Saklıdır.') }}</p>
        @stack('unsubscribe')
        <span>{{ __('Dil') }}: {{ str($locale)->upper() }}</span>
        <br>
        <span>{{ now()->toDateTimeLocalString() }}</span>
    </div>
</div>
</body>
</html>
