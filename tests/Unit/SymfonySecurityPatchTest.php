<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class SymfonySecurityPatchTest extends TestCase
{
    private array $trustedProxies;

    private int $trustedHeaders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trustedProxies = Request::getTrustedProxies();
        $this->trustedHeaders = Request::getTrustedHeaderSet();
        Request::setTrustedProxies([], -1);
    }

    protected function tearDown(): void
    {
        Request::setTrustedProxies($this->trustedProxies, $this->trustedHeaders);
        parent::tearDown();
    }

    public function test_get_access_rule_also_matches_head_without_matching_post(): void
    {
        $matcher = new MethodRequestMatcher('GET');
        $this->assertTrue($matcher->matches(Request::create('/account', 'GET')));
        $this->assertTrue($matcher->matches(Request::create('/account', 'HEAD')));
        $this->assertFalse($matcher->matches(Request::create('/account', 'POST')));
    }

    #[DataProvider('scriptNames')]
    public function test_script_name_cannot_hide_an_appended_protected_path(string $uri): void
    {
        $request = $this->scriptRequest($uri);
        $this->assertSame('', $request->getBaseUrl());
        $this->assertSame($uri, $request->getPathInfo());
    }

    public static function scriptNames(): array
    {
        return [['/index.phpadmin'], ['/index%2Ephpadmin'], ['/index.php%61dmin']];
    }

    public function test_normal_script_and_subdirectory_urls_keep_their_base_and_path(): void
    {
        $request = $this->scriptRequest('/index.php/account');
        $this->assertSame('/index.php', $request->getBaseUrl());
        $this->assertSame('/account', $request->getPathInfo());
        $request = $this->scriptRequest('/public/index.php/account', '/public/index.php');
        $this->assertSame('/public/index.php', $request->getBaseUrl());
        $this->assertSame('/account', $request->getPathInfo());
    }

    #[DataProvider('authorityPaths')]
    public function test_client_path_cannot_turn_generated_urls_into_external_urls(string $uri): void
    {
        $request = $this->scriptRequest($uri, '/public/index.php');
        $this->assertSame('', $request->getBaseUrl());
        $this->assertSame('hypotenuse.test', parse_url($request->getUriForPath('/login'), PHP_URL_HOST));
    }

    public static function authorityPaths(): array
    {
        return [['//evil.example/public/index.php/login'], ['/\\evil.example/public/index.php/login']];
    }

    #[DataProvider('publicTransitionAddresses')]
    public function test_private_proxy_shortcut_does_not_trust_public_ipv6_transition_clients(string $remote): void
    {
        Request::setTrustedProxies(['private_ranges'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PROTO);
        $request = $this->forwardedRequest($remote);
        $this->assertFalse($request->isFromTrustedProxy());
        $this->assertSame($remote, $request->getClientIp());
        $this->assertSame('hypotenuse.test', $request->getHost());
        $this->assertSame('http', $request->getScheme());
    }

    public static function publicTransitionAddresses(): array
    {
        return [['::ffff:8.8.8.8'], ['2002:0808:0808::1'], ['2001:0:1234::1'], ['64:ff9b::808:808']];
    }

    #[DataProvider('privateProxyAddresses')]
    public function test_real_private_proxies_still_forward_client_context(string $remote): void
    {
        Request::setTrustedProxies(['private_ranges'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PROTO);
        $request = $this->forwardedRequest($remote);
        $this->assertTrue($request->isFromTrustedProxy());
        $this->assertSame('10.10.10.7', $request->getClientIp());
        $this->assertSame('forwarded.example', $request->getHost());
        $this->assertSame('https', $request->getScheme());
    }

    public static function privateProxyAddresses(): array
    {
        return [['10.0.0.5'], ['::ffff:10.0.0.5'], ['fd00::5']];
    }

    public function test_subrequest_cannot_rebuild_parent_context_with_temporary_proxy_trust(): void
    {
        $context = new RequestContext;
        $stack = new RequestStack;
        $listener = new RouterListener(new UrlMatcher(new RouteCollection, $context), $stack, $context);
        $kernel = $this->createStub(HttpKernelInterface::class);
        $main = $this->forwardedRequest('127.0.0.1');
        $main->attributes->set('_controller', 'already_routed');
        $stack->push($main);
        $listener->onKernelRequest(new RequestEvent($kernel, $main, HttpKernelInterface::MAIN_REQUEST));
        $this->assertSame('hypotenuse.test', $context->getHost());

        $child = Request::create('http://hypotenuse.test/fragment');
        $child->attributes->set('_controller', 'already_routed');
        $stack->push($child);
        $listener->onKernelRequest(new RequestEvent($kernel, $child, HttpKernelInterface::SUB_REQUEST));
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PROTO);
        $listener->onKernelFinishRequest(new FinishRequestEvent($kernel, $child, HttpKernelInterface::SUB_REQUEST));

        $this->assertSame('hypotenuse.test', $context->getHost());
        $this->assertSame('http', $context->getScheme());
        $this->assertSame(80, $context->getHttpPort());
    }

    public function test_compiled_host_placeholder_does_not_consume_a_protected_path_prefix(): void
    {
        $routes = new RouteCollection;
        $routes->add('page', new Route('/page/{id}', requirements: ['tenant' => '.+'], host: '{tenant}'));
        $context = new RequestContext(host: 'hypotenuse.test');
        $matcher = new CompiledUrlMatcher((new CompiledUrlMatcherDumper($routes))->getCompiledRoutes(), $context);
        $this->assertSame('page', $matcher->match('/page/7')['_route']);
        $this->assertSame('hypotenuse.test', $matcher->match('/page/7')['tenant']);
        $this->expectException(ResourceNotFoundException::class);
        $matcher->match('/admin./page/7');
    }

    private function scriptRequest(string $uri, string $script = '/index.php'): Request
    {
        return new Request(server: [
            'REQUEST_URI' => $uri, 'SCRIPT_NAME' => $script, 'PHP_SELF' => $script,
            'SCRIPT_FILENAME' => '/var/www'.$script, 'SERVER_NAME' => 'hypotenuse.test',
            'HTTP_HOST' => 'hypotenuse.test', 'SERVER_PORT' => 80, 'REQUEST_METHOD' => 'GET',
        ]);
    }

    private function forwardedRequest(string $remote): Request
    {
        return Request::create('http://hypotenuse.test/account', server: [
            'REMOTE_ADDR' => $remote, 'HTTP_X_FORWARDED_FOR' => '10.10.10.7',
            'HTTP_X_FORWARDED_HOST' => 'forwarded.example', 'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);
    }
}
