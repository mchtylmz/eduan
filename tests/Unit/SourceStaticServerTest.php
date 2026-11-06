<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class SourceStaticServerTest extends TestCase
{
    public function test_real_php_server_downloads_html_aliases_and_preserves_other_routes(): void
    {
        $root = sys_get_temp_dir().'/eduan-source-server-'.bin2hex(random_bytes(6));
        $files = new Filesystem;
        $process = null;
        try {
            // Use public_html to exercise the configured web-root convention.
            $public = $root.'/public_html';
            $files->makeDirectory($public.'/uploads/sources/demo', 0700, true);
            $payload = '<!doctype html><script>window.sourceRawExecuted=true</script><h1>Demo</h1>';
            $files->put($public.'/uploads/sources/demo/lesson.html.download', $payload);
            $files->put($public.'/uploads/sources/demo/guide.pdf', '%PDF-1.4 test');
            $files->put($public.'/diagram.png', 'unchanged image bytes');
            $files->put($public.'/index.php', '<?php echo "Laravel fallback";');
            $files->put($root.'/outside.html.download', 'must not be streamed');
            symlink($public.'/uploads/sources/demo/lesson.html.download', $public.'/alias');
            symlink($root.'/outside.html.download', $public.'/uploads/sources/demo/escape.html.download');
            $socket = stream_socket_server('tcp://127.0.0.1:0');
            $address = stream_socket_get_name($socket, false);
            fclose($socket);
            $process = new Process([PHP_BINARY, '-S', $address, dirname(__DIR__, 2).'/server.php'], $public);
            $process->setTimeout(20)->start();
            $this->assertTrue($process->waitUntil(fn ($type, $output) => str_contains($output, 'Development Server')));
            $request = function (string $path, string $method = 'GET') use ($address): array {
                $stream = fopen('http://'.$address.$path, 'rb', false, stream_context_create(['http' => [
                    'method' => $method, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 3,
                ]]));
                $meta = stream_get_meta_data($stream);
                $body = stream_get_contents($stream);
                fclose($stream);
                $headers = [];
                foreach ($meta['wrapper_data'] as $line) {
                    if (str_contains($line, ':')) {
                        [$key, $value] = explode(':', $line, 2);
                        $headers[strtolower($key)] = trim($value);
                    }
                }
                preg_match('/HTTP\/\S+ (\d+)/', $meta['wrapper_data'][0], $status);

                return ['status' => (int) $status[1], 'headers' => $headers, 'body' => $body];
            };
            foreach (['/uploads/sources/demo/lesson.html.download', '/uploads/sources/demo/lesson.%68tml.download?download=1',
                '/uploads%2Fsources%2Fdemo%2Flesson.html.download', '/uploads/sources/demo/../demo/lesson.html.download', '/alias'] as $path) {
                $response = $request($path);
                $this->assertSame(200, $response['status'], $path);
                $this->assertSame('application/octet-stream', $response['headers']['content-type']);
                $this->assertStringStartsWith('attachment;', $response['headers']['content-disposition']);
                $this->assertSame('nosniff', $response['headers']['x-content-type-options']);
                $this->assertSame($payload, $response['body']);
            }
            $head = $request('/uploads/sources/demo/lesson.html.download', 'HEAD');
            $this->assertSame(200, $head['status']);
            $this->assertSame((string) strlen($payload), $head['headers']['content-length']);
            $this->assertSame('', $head['body']);
            $this->assertSame(405, $request('/uploads/sources/demo/lesson.html.download', 'POST')['status']);
            $this->assertSame(404, $request('/uploads/sources/demo/escape.html.download')['status']);
            $this->assertSame(404, $request('/uploads/sources/demo/missing.html.download')['status']);
            $this->assertSame('%PDF-1.4 test', $request('/uploads/sources/demo/guide.pdf')['body']);
            $this->assertSame('unchanged image bytes', $request('/diagram.png')['body']);
            $this->assertSame('Laravel fallback', $request('/sources/demo/lesson')['body']);
        } finally {
            $process?->stop();
            $files->deleteDirectory($root);
        }
    }
}
