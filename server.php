<?php

// Artisan serve uses public_path() as its working directory, including public_html.
$publicPath = getcwd();
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
if (str_contains($uri, "\0")) {
    http_response_code(400);

    return true;
}

$sourcesPath = realpath($publicPath.'/uploads/sources');
$file = realpath($publicPath.$uri);
$protectedOriginal = $sourcesPath && $file && is_file($file)
    && str_starts_with($file, $sourcesPath.DIRECTORY_SEPARATOR)
    && str_ends_with($file, '.html.download');
$requestedOriginal = str_starts_with($uri, '/uploads/sources/') && str_ends_with($uri, '.html.download');

if ($protectedOriginal || $requestedOriginal) {
    header('X-Content-Type-Options: nosniff');
    if (! $protectedOriginal || ! is_readable($file)) {
        http_response_code(404);

        return true;
    }
    if (! in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
        http_response_code(405);
        header('Allow: GET, HEAD');

        return true;
    }
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"source.html\"; filename*=UTF-8''".rawurlencode(substr(basename($file), 0, -9)));
    header('Content-Length: '.filesize($file));
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        readfile($file);
    }

    return true;
}

// Preserve Laravel's native static-file and front-controller behavior elsewhere.
return require __DIR__.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
