<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * In production every request crosses the VPS-level global Caddy and then the
 * portfolio's internal gateway before reaching Laravel. Without trusting those
 * proxies Laravel reports plain HTTP from a container address and generates
 * http:// URLs, which breaks Filament's administration assets behind TLS.
 *
 * Trust is limited to private ranges. The API publishes no host port and is
 * only reachable through the gateway, so a public client can never present one
 * of these addresses and cannot forge the request IP the rate limiters use.
 */
final class TrustedProxyTest extends TestCase
{
    /**
     * The URI is absolute so the request carries a real Host header, exactly
     * as Caddy forwards it. Its scheme stays http: anything reporting the
     * request as secure must come from the trusted X-Forwarded-Proto.
     */
    private function requestThroughProxy(string $proxyAddress, array $headers = []): Request
    {
        $captured = null;

        $this->app['router']->get('/__trusted-proxy-probe', function (Request $request) use (&$captured): string {
            $captured = $request;

            return 'ok';
        });

        $this->call(
            method: 'GET',
            uri: 'http://lucianogonzalez.dev/__trusted-proxy-probe',
            server: ['REMOTE_ADDR' => $proxyAddress] + $headers,
        )->assertOk();

        return $captured;
    }

    public function test_it_honours_the_forwarded_scheme_from_a_private_proxy(): void
    {
        $request = $this->requestThroughProxy('172.18.0.4', [
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        $this->assertTrue($request->isSecure(), 'A forwarded HTTPS request must be reported as secure.');
        $this->assertSame('lucianogonzalez.dev', $request->getHost());
        $this->assertStringStartsWith('https://lucianogonzalez.dev', url('/admin'));
    }

    public function test_it_takes_the_host_from_the_host_header_not_a_forwarded_one(): void
    {
        // X-Forwarded-Host is not in the trusted header mask, so a visitor
        // cannot poison generated URLs even through a trusted proxy chain.
        $request = $this->requestThroughProxy('172.18.0.4', [
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'attacker.example',
        ]);

        $this->assertSame('lucianogonzalez.dev', $request->getHost());
        $this->assertStringStartsWith('https://lucianogonzalez.dev', url('/admin'));
    }

    public function test_it_resolves_the_client_address_through_a_proxy_chain(): void
    {
        // Each hop appends its own peer, so the client is the leftmost entry
        // and every trusted private hop to its right is discarded.
        $request = $this->requestThroughProxy('172.18.0.4', [
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7, 10.1.2.3',
        ]);

        $this->assertSame('203.0.113.7', $request->ip());
    }

    public function test_it_does_not_adopt_a_private_address_injected_by_the_client(): void
    {
        // A visitor prepending a private address must not be able to make
        // Laravel treat it as the request IP the rate limiters key on.
        $request = $this->requestThroughProxy('172.18.0.4', [
            'HTTP_X_FORWARDED_FOR' => '10.0.0.1, 203.0.113.7',
        ]);

        $this->assertSame('203.0.113.7', $request->ip());
    }

    public function test_a_wildcard_trusted_proxy_setting_is_rejected(): void
    {
        // Trusting every proxy would turn the forwarded headers into
        // client-controlled input, so a wildcard is filtered out rather than
        // honoured, and the request falls back to the untrusted behaviour.
        putenv('TRUSTED_PROXIES=*');
        $_ENV['TRUSTED_PROXIES'] = '*';

        try {
            $this->refreshApplication();

            $request = $this->requestThroughProxy('198.51.100.9', [
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
            ]);

            $this->assertFalse($request->isSecure(), 'A wildcard must not make every sender trusted.');
            $this->assertSame('198.51.100.9', $request->ip());
        } finally {
            putenv('TRUSTED_PROXIES');
            unset($_ENV['TRUSTED_PROXIES']);
        }
    }

    public function test_it_ignores_forwarded_headers_from_an_untrusted_public_address(): void
    {
        $request = $this->requestThroughProxy('198.51.100.9', [
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
        ]);

        $this->assertFalse($request->isSecure(), 'An untrusted sender must not be able to claim HTTPS.');
        $this->assertSame(
            '198.51.100.9',
            $request->ip(),
            'An untrusted sender must not be able to spoof the rate-limited client address.',
        );
    }
}
