<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\Sources\SourceInput;
use App\Helpers\Sources\SourceStorage;
use App\Http\Controllers\Controller;
use App\Models\SourceFile;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SourceController extends Controller
{
    public function show(Request $request, string $code, string $slug)
    {
        $source = SourceFile::where('code', $code)->where('slug', $slug)->where('status', 'ready')->firstOrFail();
        $cache = Cache::store('security');
        $limiter = new RateLimiter($cache);
        $key = 'source-view:'.hash('sha256', $request->ip().'|'.$source->code);
        $cache->lock($key.':lock', 5)->block(2, function () use ($limiter, $key) {
            abort_if($limiter->tooManyAttempts($key, 3), 429, __('sources.too_many'), ['Retry-After' => max(1, $limiter->availableIn($key))]);
            $limiter->hit($key, 60);
        });
        $returnPath = SourceInput::returnPath($request->query('return_to'));
        $label = str_starts_with($returnPath, '/test/') || str_starts_with($returnPath, '/exam/') ? 'sources.return_test' : (str_starts_with($returnPath, '/lesson/') ? 'sources.return_lesson' : 'sources.return_site');

        return response()->view('frontend.sources.show', compact('source', 'returnPath', 'label') + ['title' => $source->title])
            ->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function html(string $code, SourceStorage $storage)
    {
        $source = SourceFile::where('code', $code)->where('status', 'ready')->where('extension', 'html')->firstOrFail();
        $stream = $storage->htmlStream($source);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, ['Content-Type' => 'text/html; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow',
            'Content-Security-Policy' => "sandbox; default-src 'none'; style-src 'unsafe-inline'; img-src data: https:; font-src data: https:; base-uri 'none'; form-action 'none'",
            'Referrer-Policy' => 'no-referrer']);
    }
}
